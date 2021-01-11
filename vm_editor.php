<?php
function generateDates($start, $end)
{
  $result = [];
  while ($start <= $end) {
	$date = $start->format('Y').'-'.$start->format('m').'-'.$start->format('d');
    $result[$date] = $date;
    $start->add(new DateInterval('P1D'));
  }
  return $result;
}
$start = new DateTime('2020-08-01');
$end = new DateTime('2020-08-31');
$yearlyMonthData = generateDates($start, $end);
print_r($yearlyMonthData);