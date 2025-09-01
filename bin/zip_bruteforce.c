#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <unistd.h>
#include <signal.h>
#include <sys/wait.h>
#include <zip.h>
#include <zlib.h>

#define PASSWORD_LENGTH 6
#define FAST_READ_BYTES (16 * 1024)

// 快速验证：只读前 FAST_READ_BYTES
int quick_check(zip_t* za) {
    zip_int64_t num_entries = zip_get_num_entries(za, 0);
    for (zip_uint64_t i = 0; i < num_entries; i++) {
        struct zip_stat st;
        if (zip_stat_index(za, i, 0, &st) != 0 || st.size == 0) continue;
        zip_file_t* zf = zip_fopen_index(za, i, 0);
        if (!zf) continue;
        char buffer[FAST_READ_BYTES];
        zip_int64_t n = zip_fread(zf, buffer, sizeof(buffer));
        zip_fclose(zf);
        if (n > 0) return 1; // 能读出来数据，说明密码可能对
    }
    return 0;
}

// 完整 CRC 校验
int full_crc_check(zip_t* za) {
    zip_int64_t num_entries = zip_get_num_entries(za, 0);
    for (zip_uint64_t i = 0; i < num_entries; i++) {
        struct zip_stat st;
        if (zip_stat_index(za, i, 0, &st) != 0 || st.size == 0) continue;
        zip_file_t* zf = zip_fopen_index(za, i, 0);
        if (!zf) continue;
        unsigned long crc = crc32(0L, Z_NULL, 0);
        char buf[8192];
        zip_int64_t total = 0;
        while (1) {
            zip_int64_t n = zip_fread(zf, buf, sizeof(buf));
            if (n < 0) {
                zip_fclose(zf);
                return 0;
            }
            if (n == 0) break;
            crc = crc32(crc, (unsigned char*)buf, n);
            total += n;
        }
        zip_fclose(zf);
        if ((st.valid & ZIP_STAT_CRC) && total == st.size && crc == st.crc) {
            return 1;
        }
    }
    return 0;
}

int try_password(const char* zip_path, const char* password) {
    int err = 0;
    zip_t* za = zip_open(zip_path, 0, &err);
    if (!za) return 0;
    if (zip_set_default_password(za, password) < 0) {
        zip_close(za);
        return 0;
    }
    int ok = 0;
    if (quick_check(za)) {
        if (full_crc_check(za)) {
            ok = 1;
        } else {
            ok = 1;
        }
    }
    zip_close(za);
    return ok;
}

void crack_range(const char* zip_path, int start, int end, int pipe_fd) {
    char password[PASSWORD_LENGTH + 1];
    password[PASSWORD_LENGTH] = '\0';

    for (int i = start; i <= end; i++) {
        snprintf(password, sizeof(password), "%06d", i);
        if (try_password(zip_path, password)) {
            write(pipe_fd, password, PASSWORD_LENGTH);
            exit(0);
        }
    }
    exit(1);
}

int main(int argc, char* argv[]) {
    if (argc < 2) {
        printf("用法: %s zip文件路径\n", argv[0]);
        return 1;
    }
    const char* zip_path = argv[1];
    // 动态获取 CPU 核心数
    int cpu_count = sysconf(_SC_NPROCESSORS_ONLN);
    if (cpu_count <= 0) cpu_count = 4; // 获取失败就默认用 4
    int process_count = cpu_count;
    int range_per_proc = 1000000 / process_count;
    int pipefd[2];
    if (pipe(pipefd) == -1) {
        perror("pipe");
        return 1;
    }
    pid_t pids[process_count];
    for (int i = 0; i < process_count; i++) {
        int start = i * range_per_proc;
        int end = (i == process_count - 1) ? 999999 : (start + range_per_proc - 1);
        pid_t pid = fork();
        if (pid < 0) {
            perror("fork");
            return 1;
        } else if (pid == 0) {
            close(pipefd[0]); // 子进程关闭读端
            crack_range(zip_path, start, end, pipefd[1]);
        } else {
            pids[i] = pid;
        }
    }
    close(pipefd[1]); // 父进程关闭写端
    char found_password[PASSWORD_LENGTH + 1] = {0};
    int n = read(pipefd[0], found_password, PASSWORD_LENGTH);
    if (n > 0) {
        found_password[PASSWORD_LENGTH] = '\0';
        printf("%s\n", found_password);
        // 杀掉其他子进程
        for (int i = 0; i < process_count; i++) {
            kill(pids[i], SIGTERM);
        }
    } else {
        printf("未找到密码\n");
    }
    // 等待所有子进程结束
    for (int i = 0; i < process_count; i++) {
        waitpid(pids[i], NULL, 0);
    }
    return 0;
}
