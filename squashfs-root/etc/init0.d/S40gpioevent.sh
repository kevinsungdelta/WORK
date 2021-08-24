#!/bin/sh
echo [$0]: $1 ... > /dev/console
if [ "$1" = "start" ]; then
	event "STATUS.READY"		add "usockc /var/gpio_ctrl STATUS_GREEN"
	event "STATUS.CRITICAL"		add "usockc /var/gpio_ctrl INET_BLINK_SLOW"
	event "STATUS.NOTREADY"		add "usockc /var/gpio_ctrl STATUS_AMBER"
	
	event "STATUS.GREEN"		add "usockc /var/gpio_ctrl STATUS_GREEN"
	event "STATUS.GREEBBLINK"	add "usockc /var/gpio_ctrl STATUS_GREEN_BLINK"
#	event "STATUS.GREEN.BLINK.SLOW"		add "usockc /var/gpio_ctrl STATUS_GREEN_BLINK_SLOW"
#	event "STATUS.GREEN.BLINK.NORMAL"	add "usockc /var/gpio_ctrl STATUS_GREEN_BLINK_NORMAL"
#	event "STATUS.GREEN.BLINK.FAST"		add "usockc /var/gpio_ctrl STATUS_GREEN_BLINK_FAST"
	
	event "STATUS.AMBER"		add "usockc /var/gpio_ctrl STATUS_AMBER"
	event "STATUS.AMBERBLINK"	add "usockc /var/gpio_ctrl STATUS_AMBER_BLINK"
#	event "STATUS.AMBER.BLINK.SLOW"		add "usockc /var/gpio_ctrl STATUS_AMBER_BLINK_SLOW"
#	event "STATUS.AMBER.BLINK.NORMAL"	add "usockc /var/gpio_ctrl STATUS_AMBER_BLINK_NORMAL"
#	event "STATUS.AMBER.BLINK.FAST"		add "usockc /var/gpio_ctrl STATUS_AMBER_BLINK_FAST"

	event "WAN-1.PPP.EARLY"		add "phpsh /etc/scripts/update_wanled.php EVENT=WAN_PPP_EARLY"
	event "WAN-1.CONNECTED"		insert "WANLED:phpsh /etc/scripts/update_wanled.php EVENT=WAN_CONNECTED"
	event "WAN-1.PPP.ONDEMAND"	insert "WANLED:phpsh /etc/scripts/update_wanled.php EVENT=WAN_PPP_ONDEMAND"
	event "WAN-2.CONNECTED"		add "null"
	event "WAN-1.DISCONNECTED"	insert "WANLED:phpsh /etc/scripts/update_wanled.php EVENT=WAN_DISCONNECTED"
	event "WAN-2.DISCONNECTED"	add "null"
	event "BAND5G-1.LED.ON"		add "usockc /var/gpio_ctrl WIFI5_LED_ON"
	event "BAND5G-1.LED.OFF"	add "usockc /var/gpio_ctrl WIFI5_LED_OFF"
	event "BAND24G-1.LED.ON"	add "usockc /var/gpio_ctrl WIFI2_LED_ON"
	event "BAND24G-1.LED.OFF"	add "usockc /var/gpio_ctrl WIFI2_LED_OFF"
	event "WIFISTA-1.LED.ON"	add "phpsh /etc/scripts/update_bridgeled.php EVENT=LED_ON"
	event "WIFISTA-1.LED.OFF"	add "phpsh /etc/scripts/update_bridgeled.php EVENT=LED_OFF"
	event "BAND24G.ASSOCIATED"	add "phpsh /etc/scripts/update_bridgeled.php EVENT=BAND24G_ASSOCIATED"
	event "BAND5G.ASSOCIATED"	add "phpsh /etc/scripts/update_bridgeled.php EVENT=BAND5G_ASSOCIATED"
	event "WPS.INPROGRESS"		add "phpsh /etc/scripts/update_wpsled.php EVENT=WPS_IN_PROGRESS"
	event "WPS.SUCCESS"		add "phpsh /etc/scripts/update_wpsled.php EVENT=WPS_SUCCESS"
	event "WPS.OVERLAP"		add "phpsh /etc/scripts/update_wpsled.php EVENT=WPS_OVERLAP"
	event "WPS.ERROR"		add "phpsh /etc/scripts/update_wpsled.php EVENT=WPS_ERROR"
	event "WPS.NONE"		add "phpsh /etc/scripts/update_wpsled.php EVENT=WPS_NONE"
	
	event "INET_UNLIGHT"	add "usockc /var/gpio_ctrl INET_UNLIGHT"
	event "INET_RECOVER"	add "usockc /var/gpio_ctrl INET_RECOVER"

#	event "INET.GREEN.BLINK.SLOW"		add "usockc /var/gpio_ctrl INET_GREEN_SLOW"
#	event "INET.GREEN.BLINK.NORMAL"		add "usockc /var/gpio_ctrl INET_GREEN_NORMAL"
#	event "INET.GREEN.BLINK.FAST"		add "usockc /var/gpio_ctrl INET_GREEN_FAST"
#	event "INET.AMBER.BLINK.SLOW"		add "usockc /var/gpio_ctrl INET_ORANGE_SLOW"
#	event "INET.AMBER.BLINK.NORMAL"		add "usockc /var/gpio_ctrl INET_ORANGE_NORMAL"
#	event "INET.AMBER.BLINK.FAST"		add "usockc /var/gpio_ctrl INET_ORANGE_FAST"

fi
