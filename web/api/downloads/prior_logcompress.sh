#!/bin/bash
c_logcompress(){
name=$1;
echo '---'$name'日志打包开始！---';

if [ -d call/$name/ ]
then
echo "tar：$name => $name.tar.gz";
tar zcf call/$name.tar.gz call/$name/;
if [ -f call/$name.tar.gz ]
then
echo "rm：$name/";
rm -rf call/$name/;
fi;
sleep 5;
fi;
if [ -d sms/$name/ ]
then
echo "tar：$name => $name.tar.gz";
tar zcf sms/$name.tar.gz sms/$name/;
if [ -f sms/$name.tar.gz ]
then
echo "rm：$name/";
rm -rf sms/$name/;
fi;
sleep 5;
fi;
if [ -d status/$name/ ]
then
echo "tar：$name => $name.tar.gz";
tar zcf status/$name.tar.gz status/$name/;
if [ -f status/$name.tar.gz ]
then
echo "rm：$name/";
rm -rf status/$name/;
fi;
sleep 5;
fi;

echo '---'$name'日志打包完成！---';
sleep 10;
}
c_exit(){
echo '-----------------------------';
if [ $# -lt 1 ]
then
echo `date '+%Y-%m-%d %H:%M:%S'` '打包程序执行结束！';
else
echo `date '+%Y-%m-%d %H:%M:%S'` '打包程序执行退出！';
fi
exit;
}
echo `date '+%Y-%m-%d %H:%M:%S'` '打包程序执行开始！';
echo '-----------------------------';
if [ $# -lt 1 ]
then
echo '请输入打包日志日期参数(格式如：20130701)！';
c_exit 1;
fi;
endDate='';
day='';
if [ $1 ]
then
endDate=$1;
fi;
if [ $2 ]
then
day=$2;
else
day=0;
fi;
if [ $day -eq 0 ]
then
echo '开始执行'$endDate'的日志打包操作！';
else
echo '开始执行'$endDate'之前'$day'天的日志打包操作！';
fi;
for((k=0;k<$day+1;k++));
do
tim=`date '+%Y%m%d' -d "-$k day $endDate"`;
c_logcompress $tim;
done
c_exit 0;