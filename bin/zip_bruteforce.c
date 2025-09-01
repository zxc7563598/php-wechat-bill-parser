#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <unistd.h>
#include <signal.h>
#include <sys/wait.h>
#include <zip.h>

#define PASSWORD_LENGTH 6
#define PROCESS_COUNT 4

int try_password(const char* zip_path, const char* password) {
    int err = 0;
    zip_t* za = zip_open(zip_path, 0, &err);
    if (!za) return 0;
    if (zip_set_default_password(za, password) < 0) {
        zip_close(za);
        return 0;
    }
    zip_int64_t num_entries = zip_get_num_entries(za, 0);
    int success = 0;
    for (zip_uint64_t i = 0; i < num_entries; i++) {
        struct zip_stat st;
        if (zip_stat_index(za, i, 0, &st) != 0 || st.size == 0) continue;
        zip_file_t* zf = zip_fopen_index(za, i, 0);
        if (!zf) continue;
        char* buffer = malloc(st.size);
        if (!buffer) {
            zip_fclose(zf);
            continue;
        }
        zip_int64_t total_read = zip_fread(zf, buffer, st.size);
        zip_fclose(zf);
        free(buffer);
        // 必须完整读到才算成功
        if (total_read == st.size) {
            success = 1;
            break;
        }
    }
    zip_close(za);
    return success;
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
    int range_per_proc = 1000000 / PROCESS_COUNT;
    int pipefd[2];

    if (pipe(pipefd) == -1) {
        perror("pipe");
        return 1;
    }

    pid_t pids[PROCESS_COUNT];

    for (int i = 0; i < PROCESS_COUNT; i++) {
        int start = i * range_per_proc;
        int end = (i == PROCESS_COUNT - 1) ? 999999 : (start + range_per_proc - 1);

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
        for (int i = 0; i < PROCESS_COUNT; i++) {
            kill(pids[i], SIGTERM);
        }
    } else {
        printf("未找到密码\n");
    }

    // 等待所有子进程结束
    for (int i = 0; i < PROCESS_COUNT; i++) {
        waitpid(pids[i], NULL, 0);
    }

    return 0;
}
