#! /bin/bash
name=`date +%Y%m%d --date='7 days ago'`;
echo `date '+%Y-%m-%d %H:%M:%S'`" 打包程序执行开始！";
echo '---'$name'日志打包开始！---';

if [ -d call/$name/ ]
then
echo "tar：$name => $name.tar.gz";
tar zcf call/$name.tar.gz call/$name/;
if [ -f call/$name.tar.gz ]
then
echo "rm：all.log.$name-$i";
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
echo "rm：all.log.$name-$i";
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
echo "rm：all.log.$name-$i";
rm -rf status/$name/;
fi;
sleep 5;
fi;

echo '---'$name'日志打包完成！---';
echo `date '+%Y-%m-%d %H:%M:%S'`" 打包程序执行完成！";