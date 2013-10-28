<?php 

if (!isset($argv[1]) or !isset($argv[2])) {
	echo "Usage: php poller_class.php 192.168.12.12 public".PHP_EOL;
	exit(1);
}

$obj = new poller;
$config = array( 'host' => $argv[1], 'interval' => 2, 'cname' => $argv[2]);
$obj->main($config);

class poller {
	function poll_in($config) {
		snmp_set_valueretrieval(SNMP_VALUE_LIBRARY);
		snmp_set_oid_output_format(SNMP_OID_OUTPUT_NUMERIC);
		$in = snmp2_real_walk($config['host'], $config['cname'], "IF-MIB::ifHCInOctets");
		$out = snmp2_real_walk($config['host'], $config['cname'], "IF-MIB::ifHCOutOctets");
		$main = array();

		foreach ($in as $k => $v) {
			$k = array_pop(explode('.', $k));
			$v = (int)trim(rtrim(array_pop(explode(':',$v))));
			
			$main[$k] = array( 'ifindex' => $k, 'in_value' => $v);
		}

		foreach ($out as $k => $v) {
			$k = array_pop(explode('.', $k));
			$v = (int)trim(rtrim(array_pop(explode(':',$v))));
			
			$main[$k]['out_value'] = $v;
		}
		return $main;
	}

	function main($config) {
		while (1) {
			$this->process($config);
		}
	}

	function process($config) {
		$change = $config['interval'];
		$obj = new poller;
		$old = $this->poll_in($config);
		sleep($change);
		$new = $this->poll_in($config);

		echo "\n\n\n";
		echo "============================================".PHP_EOL;
		foreach ($new as $k => $v) {
			$p_in = $old[$k]['in_value'];
			$c_in = $v['in_value'];
			$delta_in = $c_in - $p_in;
			$rate_in_bytes = $delta_in / $change;
			$rate_in_bits = $rate_in_bytes * 8;
			$rate_in_kilobits = $rate_in_bits / 1024;
			$rate_in_megabits = $rate_in_kilobits / 1024;
			$rate_in = round($rate_in_megabits,2);

			$p_out = $old[$k]['out_value'];
			$c_out = $v['out_value'];
			$delta_out = $c_out - $p_out;
			$rate_out_bytes = $delta_out / $change;
			$rate_out_bits = $rate_out_bytes * 8;
			$rate_out_kilobits = $rate_out_bits / 1024;
			$rate_out_megabits = $rate_out_kilobits / 1024;
			$rate_out = round($rate_out_megabits,2);

			echo "$k: D in:$delta_in D out:$delta_out - IN: $rate_in mbps OUT: $rate_out mbps".PHP_EOL;	
		}
		echo "============================================".PHP_EOL;
	}
}


?>

