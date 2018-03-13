#! /bin/sh
echo "Hallo from Gaia data preperation script<br>"
cd gaia2
pwd
echo "<br>"
# Make sure directory (e.g. 'gaia') is writing rights i.e.: chmod g+w,u+w,o+w gaia
tar xvf vmsplit3_aa.tar
tar xvf vmsplit3_ab.tar
tar xvf vmsplit3_ac.tar
echo "<br>"
date >log.txt
../vmsplat
tar xzf COOPANS*.tgz
#Remove .tgz otherwise the old file could to read on next week upload
rm *.tgz
cd ..

