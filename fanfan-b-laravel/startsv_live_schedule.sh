#echo "export SOCCER_ROOT=$(pwd)" >> /etc/profile
#echo "export ENV_SOCCER_ROOT=$(pwd)" >> /etc/profile
#source /etc/profile

apt-get install supervisor -y
apt-get install  inotify-tools -y  


rm /etc/supervisor/conf.d/*

cp supervisord.conf /etc/supervisord.conf

# 프로젝트 별 supervioser conf 파일
cp supervisor_*.conf /etc/supervisor/conf.d/
#
supervisord

sleep 3

systemctl enable supervisor.service >> /dev/null 2>&1
#systemctl restart supervisor.service >> /dev/null 2>&1

supervisorctl reread
supervisorctl restart all
sleep 1
#supervisorctl reload
supervisorctl update
