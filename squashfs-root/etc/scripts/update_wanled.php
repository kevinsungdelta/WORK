<?
//this script needs argument EVENT, we need this to control WAN LED

include "/htdocs/phplib/xnode.php";

echo "#!/bin/sh\n";

function wan_has_ip()
{
	$wan_inf = XNODE_getpathbytarget("/runtime", "inf", "uid", "WAN-1", 0);
	if($wan_inf == "")
		return 0;

	$addrtype = get("x", $wan_inf."/inet/addrtype");
	$ipaddr = "";

	if($addrtype == "ppp4")
	{
		$ipaddr = get("x", $wan_inf."/inet/ppp4/local");
	}

	if($addrtype == "ipv4")
	{
		$ipaddr = get("x", $wan_inf."/inet/ipv4/ipaddr");
	}

	if($ipaddr == "")
	{
		return 0;
	}
	else
	{
		return 1;
	}
}

function dialup_is_manual($uid)
{
	$inf = XNODE_getpathbytarget("", "inf", "uid", $uid, 0);
	if($inf == "")
	{
		return 0;
	}

	$inet_uid = get("x", $inf."/inet");
	if($inet_uid == "")
	{
		return 0;
	}

	$inet = XNODE_getpathbytarget("/inet", "entry", "uid", $inet_uid, 0);
	if($inet == "")
	{
		return 0;
	}

	if(get("x", $inet."/ppp4/dialup/mode") == "manual")
	{
		return 1;
	}
	else
	{
		return 0;
	}
}

if($EVENT == "WAN_CONNECTED")
{
	echo "wan_port_status=`psts -i 4`\n";
	echo "if [ \"$wan_port_status\" != \"\" ]; then\n";
	echo "usockc /var/gpio_ctrl INET_ON\n";
	echo "fi\n";
}

if($EVENT == "WAN_DISCONNECTED")
{
	echo "usockc /var/gpio_ctrl INET_OFF\n";
}

if($EVENT == "WAN_PPP_ONDEMAND")
{
	echo "usockc /var/gpio_ctrl INET_BLINK_SLOW\n";
}

if($EVENT == "WAN_PPP_DIALUP")
{
	echo "usockc /var/gpio_ctrl INET_OFF\n";
}

if($EVENT == "WAN_PPP_EARLY")
{
	if(dialup_is_manual("WAN-1") == 1)
	{
		echo "usockc /var/gpio_ctrl INET_BLINK_SLOW\n";
	}
	else
	{
		echo "usockc /var/gpio_ctrl INET_OFF\n";
	}
}

if($EVENT == "WAN_PPP_HANGUP")
{
	if(dialup_is_manual("WAN-1") == 1)
	{
		echo "usockc /var/gpio_ctrl INET_BLINK_SLOW\n";
	}
}

if($EVENT == "WAN_LINKUP")
{
	if(wan_has_ip() != 0)
	{
		echo "usockc /var/gpio_ctrl INET_ON\n";
	}
}

if($EVENT == "WAN_LINKDOWN")
{
	echo "usockc /var/gpio_ctrl INET_OFF\n";
}

?>
