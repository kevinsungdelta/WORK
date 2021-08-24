<?
include "/htdocs/phplib/trace.php";
include "/htdocs/phplib/xnode.php";
include "/htdocs/phplib/inf.php";

$SAMBAP		= "/var/etc/samba";
$SAMBACFG	= $SAMBAP."/smb.conf";
$MNTROOT	= "/var/tmp/storage";
$user_name_list = "";
$partition_count = query("/runtime/device/storage/disk/count");

fwrite("w",$START, "#!/bin/sh\n");

function bwc_check()
{
	$bwc_enable = 0;
	foreach ("/bwc/entry")
	{
		if (query("enable") == 1 && query("uid") != "" )
		{
			$bwc_enable = 1;
		}
	}
	return $bwc_enable;
}

/* enable when USB is pluged and /samba/enable is not 0 */
if($partition_count!="" && $partition_count!="0" && get("", "/samba/enable")!=0)
{
	//$mntp = query("/runtime/device/storage/disk/entry:1/mntp");
	fwrite("a",$START, "if [ ! -d ".$SAMBAP." ]; then mkdir -p ".$SAMBAP."; fi\n");
	//fwrite("a",$START, "if [ ! -f ".$SAMBAP."/smbpasswd ]; then adduser nobody; smbpasswd -a nobody -n; fi\n");

	/* SAMBA authentication*/
	$auth=query("/samba/auth");
	if($auth == 1)
	{
		foreach("/device/account/entry")
		{
			$user_name = query("name");
			if(tolower($user_name)=="admin")
			{
			    $user_passwd = get("s", "password");
			    fwrite("a",$START, "adduser ".$user_name."; ( echo \"".$user_passwd."\"; echo \"".$user_passwd."\" ) | smbpasswd -s -a ".$user_name."\n");
	            fwrite("a",$STOP, "smbpasswd -x ".$user_name.";deluser ".$user_name."\n");
			    $user_name_list=$user_name;
			    break;
			}
		}
	}

	//config the network parameters for samba throughput!
	//enable ethernet driver workqueue for samba write performance
	fwrite("a",$START, "echo 1 > /sys/module/et/parameters/txworkq\n");
	fwrite("a",$START, "echo 3 > /proc/irq/169/smp_affinity\n");
	fwrite("a",$START, "echo 3 > /proc/irq/163/smp_affinity\n");

	//enable general receive offload for samba write performance
	//for Broadcom SDK, we will get CPU deadlock when enable this (tom, 20140116)
	//gro will effect QoS bandwith setting, throughput will greatly lower than expected rate limit (when without fastnat),
	//and will effect TCP packets retransmission or out-of-order (when has fastnat),
	//so disable gro when traffic control enable (sammy, 20140212)
	if( bwc_check() == 0 )
		{ fwrite("a",$START, "echo \"-gro 2\" >> /proc/net/vlan/eth0.1\n"); }

	//move samba daeomn to CPU2 for samba read/write performance
	fwrite("a",$START, "taskset -c 1 \"/sbin/smbd\"\n");
	fwrite("a",$START, "nmbd -D\n");

	fwrite("w",$STOP,  "#!/bin/sh\n");
	fwrite("a",$STOP,  "killall nmbd\n");
	fwrite("a",$STOP,  "killall smbd\n");
	fwrite("a",$STOP,  "rm -rf ".$SAMBAP."\n");

	//for Broadcom SDK, we will get CPU deadlock when enable this (tom, 20140116)
	if( bwc_check() == 0 )
		{ fwrite("a",$STOP, "echo \"-gro 0\" >> /proc/net/vlan/eth0.1\n"); }
	fwrite("a",$STOP, "echo 0 > /sys/module/et/parameters/txworkq\n");
	fwrite("a",$STOP, "echo 2 > /proc/irq/169/smp_affinity\n");
	fwrite("a",$STOP, "echo 2 > /proc/irq/163/smp_affinity\n");

	fwrite("w",$SAMBACFG, "[global]\n");
	fwrite("a",$SAMBACFG, "\tunix charset = UTF8\n");
	fwrite("a",$SAMBACFG, "\tworkgroup = WORKGROUP\n");
	fwrite("a",$SAMBACFG, "\tserver string = ".query("/runtime/device/modelname")."\n");
	fwrite("a",$SAMBACFG, "\tnetbios name = ".query("/runtime/device/modelname")."\n");
	fwrite("a",$SAMBACFG, "\twinbind nested groups = no\n");
	fwrite("a",$SAMBACFG, "\tdomain master = no\n");
	fwrite("a",$SAMBACFG, "\tbind interfaces only = yes\n");
	fwrite("a",$SAMBACFG, "\tinterfaces = ".INF_getcfgipaddr("LAN-1")."/".INF_getcfgmask("LAN-1")."\n");

	if($auth=="1")
	    {fwrite("a",$SAMBACFG, "\tsecurity = user\n");}
	else
	    {fwrite("a",$SAMBACFG, "\tsecurity = share\n");}

	fwrite("a",$SAMBACFG, "\tsocket options = IPTOS_LOWDELAY IPTOS_THROUGHPUT TCP_NODELAY TCP_FASTACK SO_KEEPALIVE SO_RCVBUF=65536 SO_SNDBUF=65536\n");
	fwrite("a",$SAMBACFG, "\tdns proxy = no\n");
	fwrite("a",$SAMBACFG, "\tguest ok = yes\n");
	fwrite("a",$SAMBACFG, "\tload printers = no\n");
	fwrite("a",$SAMBACFG, "\tbrowseable = yes\n");
	fwrite("a",$SAMBACFG, "\twriteable = yes\n");
	fwrite("a",$SAMBACFG, "\tpublic = yes\n");
	fwrite("a",$SAMBACFG, "\toplocks = no\n");
	fwrite("a",$SAMBACFG, "\tcreate mask = 0777\n");
	fwrite("a",$SAMBACFG, "\tdirectory mask = 0777\n");
	fwrite("a",$SAMBACFG, "\tmax connections = 8\n");
	//fwrite("a",$SAMBACFG, "\tread size = 32768\n");
	//fwrite("a",$SAMBACFG, "\tread prediction = true\n");
	fwrite("a",$SAMBACFG, "\tfollow symlinks = no\n");

	fwrite("a",$SAMBACFG, "\tuse sendfile = yes\n");
	fwrite("a",$SAMBACFG, "\tuse receivefile = yes\n");
	
	if(isfile("/var/etc/silex/smb.dir.conf")==1)	/*for shareport*/
	{
		fwrite("a",$SAMBACFG, "\tinclude = /var/etc/silex/smb.dir.conf\n");
		   if($auth == 1)
        {
        fwrite("a",$SAMBACFG, "\tvalid users = ".$user_name_list."\n");
        }
	}
	else /*for usbmount*/
	{
		foreach("/runtime/device/storage/disk")
		{
			$disk_n=$InDeX;
			foreach("entry")
			{
				$mntpath = query("/runtime/device/storage/disk:".$disk_n."/entry:".$InDeX."/mntp");
				$mntname = cut($mntpath, 4, "/");
				fwrite("a",$SAMBACFG, "[".$mntname."]\n");
				fwrite("a",$SAMBACFG, "\tcomment = Temporary file space\n");
				fwrite("a",$SAMBACFG, "\tpath = ".$mntpath."\n");
				if($auth == 1)
                {
                    fwrite("a",$SAMBACFG, "\tvalid users = ".$user_name_list."\n");
                }
			}
		}
	}
}
else
{
	fwrite("a",$START, "echo \"SAMBA server is disabled !\" > /dev/console\n");
	fwrite("a",$STOP, "echo \"SAMBA server is disabled !\" > /dev/console\n");
}
?>
