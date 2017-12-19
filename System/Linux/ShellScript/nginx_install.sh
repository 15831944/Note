#!/bin/bash
#
# Desc: Install Nginx WebServer
# Author: vforbox <vforbox@gmail.com>
# Env: Centos 7.2

# Identity check
if [[ $(id -u) -ne 0 ]]; then
	echo "error: user must be an administrator"
	exit 1
fi

# Whether or not it has been installed
if [[ -d /usr/local/nginx || -f $(which nginx) ]]; then
	echo "Already installed"
	exit 1
fi

# System version Centos 7.x
[ -x /usr/bin/systemctl ] || exit 1;

# Settings
SOFT_SRC_DIR="/usr/local/src/"
SITE_DIR="/opt/WebSite/"
NGINX_VER="nginx-1.12.2"
NGINX_DOWN="http://nginx.org/download/"

# Install
[ ! -f $(which wget) ] && yum -y install wget > /dev/null 2>&1
wget ${NGINX_DOWN}${NGINX_VER}.tar.gz -O ${SOFT_SRC_DIR}${NGINX_VER}.tar.gz
VALUE=$? && [ ${VALUE} = 0 ] || exit 1
if [ -e "${SOFT_SRC_DIR}${NGINX_VER}.tar.gz" ]; then
	cd ${SOFT_SRC_DIR} && tar -zxf "${NGINX_VER}.tar.gz"
	VALUE=$? &&  [ ${VALUE} = 0 ] && cd ${NGINX_VER}
	sed -i "s/${NGINX_VER##*-}/2.4.29/;s/${NGINX_VER%%-*}\//Apache\//g" src/core/nginx.h
	[ ! -d ${SITE_DIR} ] && mkdir -p ${SITE_DIR}
	$(id ${NGINX_VER%%-*}) > /dev/null 2>&1 VALUE=$?
	[ ${VALUE} = 1 ] || useradd -d ${SITE_DIR} -s /sbin/nologin -M ${NGINX_VER%%-*}
	./configure \
	--user=nginx \
	--group=nginx \
	--prefix=/usr/local/nginx \
	--sbin-path=/usr/sbin/nginx \
	--conf-path=/etc/nginx/nginx.conf \
	--pid-path=/var/run/nginx.pid \
	--lock-path=/var/lock/subsys/nginx \
	--error-log-path=/var/log/nginx/error.log \
	--http-log-path=/var/log/nginx/access.log \
	--with-http_stub_status_module \
	--with-http_gzip_static_module \
	--with-http_realip_module \
	--with-http_ssl_module \
	--with-pcre
	VALUE=$? &&  [ ${VALUE} = 0 ] && make
	VALUE=$? &&  [ ${VALUE} = 0 ] && make install
fi

# Config Firewall
firewall-cmd --permanent --add-port=80/tcp
firewall-cmd --reload

# Config Nginx
wget -q https://raw.githubusercontent.com/vforbox/Note/master/System/Linux/ShellScript/nginx_server_manage.sh -O /etc/init.d/nginx
VALUE=$? && [ ${VALUE} = 0 ] || exit 1
chmod 755 /etc/init.d/nginx
chkconfig --add nginx
systemctl enable nginx.service
systemctl start nginx.service

# test
echo "Install Nginx" > /usr/local/nginx/html/index.html
curl -v http://localhost/

# Clear
rm -rf ${SOFT_SRC_DIR}${NGINX_VER}${NGINX_VER}
