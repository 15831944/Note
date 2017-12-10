#include <pthread.h>
#include <unistd.h>
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <sys/socket.h>
#include <netinet/in.h>
#include <signal.h>
#include <sys/time.h>
#include <sys/types.h>
#include <math.h>
#include <ctype.h>
#include <errno.h>
#include <arpa/inet.h>
#include <netinet/ip.h>
#include <netinet/udp.h>

/* Amplification settings */
char payload[] = "\x17\x00\x03\x2a\x00\x00\x00\x00"; // NTP payload
int port = 123; // NTP port

/*
char payload[] =
"\x30\x24\x02\x01\x01\x04\x06\x70\x75\x62\x6c\x69\x63\xa5\x17\x02"
"\x04\x7b\x73\xcc\x13\x02\x01\x00\x02\x01\x64\x30\x09\x30\x07\x06"
"\x03\x2b\x06\x01\x05";
int port = 161; // SNMP port
*/

/*
char payload[] = "\x03"; // mssql payload
port = 1434; // mssql port
*/

/*
char payload[] = "M-SEARCH * HTTP/1.1\r\nHost:239.255.255.250:1900\r\nST:ssdp:all\r\nMan:\"ssdp:discover\"\r\nMX:3\r\n\r\n"; // ssdp payload
int port = 1900; // ssdp port
*/

FILE *fd;
FILE *hostfile;
int size = sizeof(payload);
struct sockaddr_in dest;
volatile int running_threads = 0;
volatile int found_srvs = 0;
volatile unsigned long scanned = 0;
volatile int bytes_sent = 0;
volatile unsigned long hosts_done = 0;
int num = 0, n = 0;
int atleastbytes = 40;
char buf[32];
char *filename;
u_long address[1000000];

void sighandler(int sig)
{
    fclose(fd);
    printf("\n");
    exit(0);
}

void *flood(void *par1)
{
    running_threads++;
    int thread_id = (int)par1;
    unsigned char buff[65536];
    memset(buff, 0x01, 1);
    int sizeofpayload = 1;
    int sock;
    if ((sock = socket(AF_INET, SOCK_DGRAM, IPPROTO_UDP)) < 0)
    {
        perror("cant open socket");
        exit(-1);
    }
    int stop = 0;
    while (stop == 0)
    {
        if (n == num) {
            sleep(30);
            stop = 1;
        } else {
            n++;
		}
        struct in_addr destip_addr;
        ulong destaddr = address[n];
        destip_addr.s_addr=destaddr;
        dest.sin_addr.s_addr=destip_addr.s_addr;
        struct sockaddr_in servaddr;
        bzero(&servaddr, sizeof(servaddr));
        servaddr.sin_family = AF_INET;
        servaddr.sin_addr.s_addr = destip_addr.s_addr;
        servaddr.sin_port = htons(port);
        sendto(sock, payload, size, 0, (struct sockaddr *)&servaddr, sizeof(servaddr));
        bytes_sent += size;
        scanned++;
        hosts_done++;
    }
    close(sock);
    running_threads--;
}

void *recievethread()
{
    printf("\n");
    int ppip = 0;
    int saddr_size, data_size, sock_raw;
    struct sockaddr_in saddr;
    struct in_addr in;
    unsigned char *buffer = (unsigned char *)malloc(65536);
    sock_raw = socket(AF_INET , SOCK_RAW , IPPROTO_UDP);
    if (sock_raw < 0)
    {
        printf("Socket Error\n");
        exit(1);
    }
    while (1)
    {
        saddr_size = sizeof saddr;
        data_size = recvfrom(sock_raw, buffer, 65536, 0, (struct sockaddr *)&saddr, &saddr_size);
        if (data_size < 0)
        {
            printf("Recvfrom error , failed to get packets\n");
            exit(1);
        }
        struct iphdr *iph = (struct iphdr*)buffer;
        if (iph->protocol == 17)
        {
            unsigned short iphdrlen = iph->ihl * 4;
            struct udphdr *udph = (struct udphdr*)(buffer + iphdrlen);
            unsigned char* payload = buffer + iphdrlen + 8;
            if (ntohs(udph->source) == port)
            {
                int body_length = data_size - iphdrlen - 8;
                if (body_length >= atleastbytes)
                {
                    ppip++;
                    found_srvs++;
                    fprintf(fd, "%s %d\n", inet_ntoa(saddr.sin_addr), body_length, ppip);
                    fflush(fd);
                }
            }
        }
        ppip = 0;
    }
    close(sock_raw);
}

void loadfile(void)
{
    if ((hostfile = fopen(filename, "r")) == NULL)
    {
        perror("Opening hostfile");
        exit(-1);
    }

    while (fgets(buf, sizeof buf, hostfile) != NULL)
    {
        if ((buf[strlen(buf) - 1] == '\n') || (buf[strlen(buf) - 1] == '\r')) {
            buf[strlen(buf) - 1] = 0x00;
            if (num > 1000000) { break; }
            address[num] = inet_addr(buf);
            num++;
        } else {
            continue;
        }
    }
}


int main(int argc, char *argv[])
{
   	if (argc < 4)
    {
   	   	fprintf(stdout, "Coded by Spai3N\nUsage: %s [list.txt] [output.txt] [THREADS] [MinRespBytes]\n", argv[0]);
        exit(-1);
    }
   	filename = argv[1];
   	loadfile();
   	fd = fopen(argv[2], "a");
   	signal(SIGINT, &sighandler);
   	int threads = atoi(argv[3]);
   	atleastbytes = atoi(argv[4]);
   	pthread_t thread;
   	pthread_t listenthread;
   	pthread_create(&listenthread, NULL, &recievethread, NULL);
   	int i;
   	for (i = 0; i < threads; i++)
   	{
   	   	pthread_create(&thread, NULL, &flood, (void *)i);
    }
   	sleep(1);
   	printf("Scan in Progress \n");
   	char *temp = (char *)malloc(17);
   	memset(temp, 0, 17);
   	sprintf(temp, "IP Found");
   	printf("%-16s", temp);
   	memset(temp, 0, 17);
   	sprintf(temp, "IP/s");
   	printf("%-16s", temp);
   	memset(temp, 0, 17);
   	sprintf(temp, "Bytes/s");
   	printf("%-16s", temp);
    memset(temp, 0, 17);
   	sprintf(temp, "Threads");
   	printf("%-16s", temp);
    memset(temp, 0, 17);
    sprintf(temp, "Percent Done");
    printf("%s", temp);
   	printf("\n");
   	char *new;
   	new = (char *)malloc(16 * 6);
   	while (running_threads > 0)
    {
   	   	printf("\r");
   	   	memset(new, '\0', 16 * 6);
   	   	sprintf(new, "%s|%-15lu", new, found_srvs);
   	   	sprintf(new, "%s|%-15d", new, scanned);
   	   	sprintf(new, "%s|%-15d", new, bytes_sent);
   	   	sprintf(new, "%s|%-15d", new, running_threads);
   	   	memset(temp, 0, 17);
        sprintf(new, "%s|%s", new, temp);
   	   	printf("%s", new);
   	   	fflush(stdout);
        bytes_sent=0;
        scanned = 0;
        sleep(1);
    }
   	printf("\n");
    fclose(fd);
    return 0;
}
