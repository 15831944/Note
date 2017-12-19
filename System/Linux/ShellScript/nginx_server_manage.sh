#!/bin/bash
# chkconfig: - 18 21
# description: http service.

# Source Function Library
. /etc/init.d/functions

# Nginx Settings
NGINX_SBIN="/usr/sbin/nginx"
NGINX_CONF="/etc/nginx/nginx.conf"
NGINX_LOCK="/var/lock/subsys/nginx"
NGINX_PID="/var/run/nginx.pid"
PROG_NAME="nginx"
RETVAL=0

# Source networking configuration
. /etc/sysconfig/network
# Check networking is up
[[ ${NETWORKING} = "no" ]] && exit 0
[ -x ${NGINX_SBIN} ] || exit 0

start()
{
	if [ -e ${NGINX_PID} ]; then
		echo "Nginx already running ..."
		exit 1
	fi
	echo -n $"Starting ${PROG_NAME}: "
    daemon ${NGINX_SBIN} -c ${NGINX_CONF}
	RETVAL=$?
	echo
	[ ${RETVAL} = 0 ] && touch ${NGINX_LOCK}
    return ${RETVAL}
}

stop()
{
	if [ ! -e ${NGINX_PID} ]; then
		echo "Nginx not running ..."
		exit 1
	fi
	echo -n $"Stopping ${PROG_NAME}: "
    killproc -p ${NGINX_PID} ${NGINX_SBIN} -TERM
	RETVAL=$?
	echo
	[ ${RETVAL} = 0 ] && rm -rf ${NGINX_LOCK} ${NGINX_PID}
	return ${RETVAL}
}

reload()
{
	if [ ! -e ${NGINX_PID} ]; then
		echo "Nginx not running ..."
		exit 1
	fi
	echo -n $"Reloading ${PROG_NAME}: "
    killproc -p ${NGINX_PID} ${NGINX_SBIN} -HUP
	RETVAL=$?
	echo
    return ${RETVAL}
}

restart()
{
	stop
    start
}

configtest()
{
	${NGINX_SBIN} -c ${NGINX_CONF} -t
    return ${RETVAL}
}

case "$1" in
  start)
        start
        ;;
  stop)
        stop
        ;;
  reload)
        reload
        ;;
  restart)
        restart
        ;;
  configtest)
        configtest
        ;;
  *)
        echo $"Usage: $0 {start|stop|reload|restart|configtest}"
        RETVAL=1
esac
exit ${RETVAL}
