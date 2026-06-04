<?php
function numberToWords($number) {
    $amount = (int) $number;
    if ($amount == 0) {
        return "Rupees Zero Only";
    }
    
    $thousands = array('', 'Thousand', 'Lakh', 'Crore');
    $ones = array(
        '', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten',
        'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'
    );
    $tens = array('', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety');
    
    $words = [];
    
    $hundreds = $amount % 1000;
    $amount = (int) ($amount / 1000);
    
    $segments = [];
    $segments[] = $hundreds; // 0: hundreds
    
    while ($amount > 0) {
        $segments[] = $amount % 100;
        $amount = (int) ($amount / 100);
    }
    
    $numSegments = count($segments);
    for ($i = $numSegments - 1; $i >= 0; $i--) {
        $val = $segments[$i];
        if ($val == 0) {
            continue;
        }
        
        $segmentWords = "";
        if ($i == 0) {
            $h = (int) ($val / 100);
            $tens_ones = $val % 100;
            
            if ($h > 0) {
                $segmentWords .= $ones[$h] . " Hundred ";
            }
            if ($tens_ones > 0) {
                if ($h > 0) {
                    $segmentWords .= "and ";
                }
                if ($tens_ones < 20) {
                    $segmentWords .= $ones[$tens_ones] . " ";
                } else {
                    $t = (int) ($val % 100 / 10);
                    $o = $val % 10;
                    $segmentWords .= $tens[$t] . " " . $ones[$o] . " ";
                }
            }
        } else {
            if ($val < 20) {
                $segmentWords .= $ones[$val] . " ";
            } else {
                $t = (int) ($val / 10);
                $o = $val % 10;
                $segmentWords .= $tens[$t] . " " . $ones[$o] . " ";
            }
            $segmentWords .= $thousands[$i] . " ";
        }
        
        $words[] = trim($segmentWords);
    }
    
    return "Rupees " . implode(' ', $words) . " Only";
}

echo "15000 => " . numberToWords(15000) . "\n";
echo "15250 => " . numberToWords(15250) . "\n";
echo "1234567 => " . numberToWords(1234567) . "\n";
echo "0 => " . numberToWords(0) . "\n";
