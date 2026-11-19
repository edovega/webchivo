/*
 * rce_flag: installed setuid root as /usr/local/bin/rce_flag.
 * Prints /root/rce_flag.txt, which www-data cannot read directly.
 * Contains no flag.
 */
#include <stdio.h>

int main(void)
{
    char buf[256];
    size_t n;
    FILE *f = fopen("/root/rce_flag.txt", "r");

    if (f == NULL) {
        fputs("rce_flag: not available\n", stderr);
        return 1;
    }
    while ((n = fread(buf, 1, sizeof buf, f)) > 0)
        fwrite(buf, 1, n, stdout);
    fclose(f);
    return 0;
}
