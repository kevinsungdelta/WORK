#!/bin/sh

# add loop to wait for wan up.
for ii in 0 1 2 3 4 5
do
	status=`grep  nameserver /etc/resolv.conf`
	if [ "$status" == "" ]; then
		echo "dns server not ready ..."
		sleep 3
		if [ $ii == 5 ] ;then
		echo "no dns server abort fw check"
		xmldbc -t checkNewFirmware:60:/etc/events/checkfw.sh
		exit 0
		fi
	else
		break;
	fi
done 

echo "Check FW Now ..."
fwinfo="/tmp/fwinfo.xml"
model="`xmldbc -g /runtime/device/modelname`"
srv="`xmldbc -g /runtime/device/fwinfosrv`"
reqstr="`xmldbc -g /runtime/device/fwinfopath`"
old_major=`cat /etc/config/buildver|cut -d'.' -f1`
old_minor=`cat /etc/config/buildver|cut -d'.' -f2|cut -c1-2`
buildver="0"$old_major$old_minor

#+++sam_pan add for firmware onlone check.
fwcheckparameter="`xmldbc -g /device/fwcheckparameter`"
if [ "$fwcheckparameter" != "" ]; then
global=$fwcheckparameter
else
# Get hw revision sync. to hidden page (192.168.0.1/version.php) Joseph Chao
global="`xmldbc -g /runtime/devdata/hwver | sed 's/[^a-zA-Z]//g' | tr '[a-z]' '[A-Z]' | cut -c 1`"
global=$global"x_Default"
fi
#---sam_pan

#+++ HuanYao add for dlink requirement.
MAC="`xmldbc -g /runtime/devdata/lanmac | sed 's/://g'`"	# MAC will always be in lowercase.
wget_string="http://"$srv$reqstr"?model=$model\_$global\_FW\_$buildver\_$MAC"

rm -f $fwinfo
xmldbc -X /runtime/firmware
wget  $wget_string -O $fwinfo
xmldbc -s /runtime/firmware/wget_string $wget_string

if [ -f $fwinfo ]; then
	#get firmware information
	new_major=`grep Major /tmp/fwinfo.xml | sed -r 's/.*<Major>//' | sed -r 's/<\/Major>.*//'`
	new_minor=`grep Minor /tmp/fwinfo.xml | sed -r 's/.*<Minor>//' | sed -r 's/<\/Minor>.*//'`
	new_date=`grep Date /tmp/fwinfo.xml | sed -r 's/.*<Date>//' | sed -r 's/<\/Date>.*//'`
	FWDownloadUrl=`grep Firmware /tmp/fwinfo.xml | sed -r 's/.*<Firmware>//' | sed -r 's/<\/Firmware>.*//'`
	if [ "$new_major" != "" ] || [ "$new_minor" != "" ]; then
		xmldbc -s /runtime/firmware/fwversion/Major $new_major
		xmldbc -s /runtime/firmware/fwversion/Minor $new_minor
		xmldbc -s /runtime/firmware/fwversion/Date $new_date
		xmldbc -s /runtime/firmware/FWDownloadUrl $FWDownloadUrl
	fi
	if [ "$new_major" != "" ]; then
		if [ $new_major -gt $old_major -o $new_major -eq $old_major -a $new_minor -gt $old_minor ]; then
			echo "Have new Firmware"
			xmldbc -s /runtime/firmware/havenewfirmware 1
			havenewfirmware=1
			usockc /var/mydlinkeventd_usock NEW_FW
			/etc/scripts/newfwnotify.sh
			service MDNSRESPONDER restart
			sleep 5
			service NAMERESOLV restart
		fi
	else
		xmldbc -s /runtime/firmware/state "NORESPONSE"
	fi
else
	xmldbc -s /runtime/firmware/state "NORESPONSE"
fi


# D-link requirement: recheck newFirmware in 7 days. (604800 secs)
xmldbc -k checkNewFirmware
if [ "$havenewfirmware" == "" ] ; then
	xmldbc -t checkNewFirmware:604800:/etc/events/checkfw.sh
fi
