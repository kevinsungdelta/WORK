<?
/* DMZ is depends on LAN services.
 * Be sure to start LAN services first. */
include "/htdocs/phplib/trace.php";

include "/etc/services/IPTABLES/iptlib.php";
include "/htdocs/phplib/phyinf.php";

fwrite("w", $START, "#!/bin/sh\n");
fwrite("w", $STOP,  "#!/bin/sh\n");

/* Get all the LAN interface IP address */
IPT_scan_lan();

/* refresh the chain of LAN interfaces */
$j = 1;
while ($j>0)
{
	$ifname = "LAN-".$j;
	$ifpath = XNODE_getpathbytarget("", "inf", "uid", $ifname, 0);
	if ($ifpath == "") { $j = 0; break; }

	$CHAIN	= "MACF.".$ifname;

	fwrite("a", $START, "iptables -t filter -F ".$CHAIN."\n");
	fwrite("a", $STOP,  "iptables -t filter -F ".$CHAIN."\n");

	XNODE_set_var($CHAIN.".USED", "0");

	/*Add rule to ifname chain */
	$i = 0;
	$policy = query("/acl/macctrl/policy");
	$cnt = query("/acl/macctrl/count");
	if ($cnt=="") $cnt = 0;
	while ($i < $cnt)
	{
		$i++;
		anchor("/acl/macctrl/entry:".$i);

		if (query("enable")!="1") continue;

		$mac	= query("mac");
		$sch_uid= query("schedule");

		if ($mac!="")
		{
			if ($policy == "DROP")
			{
				if ($sch_uid=="")
				{
					fwrite("a", $START, "iptables -A ".$CHAIN." -m mac --mac-source ".$mac." -j LOG --log-level notice --log-prefix 'DRP:004:' \n");
					fwrite("a", $START, "iptables -A ".$CHAIN." -m mac --mac-source ".$mac." -j DROP \n");
				}
				else
				{
					IPT_fwrite_schedule("a", $START, "iptables -A ".$CHAIN." -m mac --mac-source ".$mac." -j LOG --log-level notice --log-prefix 'DRP:004:'", $sch_uid);
					IPT_fwrite_schedule("a", $START, "iptables -A ".$CHAIN." -m mac --mac-source ".$mac." -j DROP", $sch_uid);
				}
			}
			else if ($policy == "ACCEPT")
			{
				if ($sch_uid=="")
				{fwrite("a", $START, "iptables -A ".$CHAIN." -m mac --mac-source ".$mac." -j RETURN \n");}
				else
				{IPT_fwrite_schedule("a", $START, "iptables -A ".$CHAIN." -m mac --mac-source ".$mac." -j RETURN", $sch_uid);}
			}
			XNODE_set_var($CHAIN.".USED", "1");
		}
	}
	// Modified by sanding chen.  adjust  the  position of the drop rule.
	// when policy is ACCEPT,  there should be one DROP rule within a chain,  and it  must be posited at the tail of the chain.
	if ($policy == "ACCEPT")
	{
		if ($sch_uid=="")
		{
			fwrite("a", $START, "iptables -A ".$CHAIN." -j LOG --log-level notice --log-prefix 'DRP:004:' \n");
			fwrite("a", $START, "iptables -A ".$CHAIN." -j DROP \n");
		}
		else
		{
			IPT_fwrite_schedule("a", $START, "iptables -A ".$CHAIN." -j LOG --log-level notice --log-prefix 'DRP:004:'", $sch_uid);
			IPT_fwrite_schedule("a", $START, "iptables -A ".$CHAIN." -j DROP", $sch_uid);
		}
	}

	$j++;
}

/* if switch level macfilter exist, do it */
if (isfile("/usr/sbin/macfilter")==1)	{include "/etc/services/SWITCHMACFILTER.php";}

if (isfile("/etc/scripts/wlan_acl.php")==1)	{fwrite("a", $START, "phpsh /etc/scripts/wlan_acl.php \n");}

fwrite("a", $START, "exit 0\n");
fwrite("a", $STOP,  "exit 0\n");
?>
