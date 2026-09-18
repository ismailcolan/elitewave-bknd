cat > mpdf_test.php << 'EOF'
<?php
require_once __DIR__ . '/vendor/autoload.php';

$mpdf = new \Mpdf\Mpdf();
$mpdf->WriteHTML('<h1>mPDF is working</h1><p>Test successful on PHP 7.4</p>');
$mpdf->Output(__DIR__ . '/mpdf_test.pdf', \Mpdf\Output\Destination::FILE);

echo "PDF generated successfully!\n";
EOF