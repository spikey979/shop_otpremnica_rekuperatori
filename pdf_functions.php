<?php


// Function to generate invoice
function generiraj_otpremnicu($data) {
	$image_path = plugin_dir_path(__FILE__) . 'images/logo.png';

	// Ensure no output has been sent
	if (headers_sent()) {
		wp_die('Some output has already been sent. Unable to generate PDF.');
	}

	$order_id = $data["order_id"];
	$broj_otpremnice = $data["broj_otpremnice"];
	$datum_otpremnice = $data["datum_otpremnice"];

	$dodatno_polje_1 = $data["dodatno_polje_1"];
	$dodatno_polje_2 = $data["dodatno_polje_2"];
	$dodatni_prazni_retci = isset($data["dodatni_prazni_retci"]) ? intval($data["dodatni_prazni_retci"]) : 0;
	
	$left_margin = 3;

	$order = wc_get_order($order_id);

	if (!$order) {
		wp_die('Invalid order ID.');
	}

	$oib_tvrtke = $order->get_meta('OIB tvrtke');
	$ime_tvrtke = $order->get_meta('Ime tvrtke');
	$adresa_tvrtke = $order->get_meta('Adresa tvrtke');
	$grad_tvrtke = $order->get_meta('Grad tvrtke');

	//$shipping_full_name = $order->get_formatted_shipping_full_name();
	$shipping_full_name = $order->get_formatted_billing_full_name();

	$stupac2_red3 = $order->get_shipping_postcode().", ".$order->get_shipping_city();
	$stupac2_red4 = "";

	$shipping_address_2 = $order->get_shipping_address_2();
	if($shipping_address_2 != "") {
		// pomakni podatke za jedan red dolje da bi ubacio address 2 polje
		$stupac2_red4 = $stupac2_red3;
		$stupac2_red3 = $shipping_address_2;
	}

	//$shipping_address = $order->get_shipping_address_1().$shipping_address_2;
	$stupac2_red2 = $order->get_shipping_address_1();
	
	//$shipping_city = $order->get_shipping_city();
	//$shipping_postcode = $order->get_shipping_postcode();

	$obj_order_date = $order->get_date_created();
	$order_date = $obj_order_date->format('d.m.Y.');


	// Fetch items in the order
	$items = $order->get_items();

	class PDF extends tFPDF
	{
		private $image_path;

		function __construct($image_path)
		{
			parent::__construct();
			$this->image_path = $image_path;
			$this->AddFont('DejaVu','','DejaVuSans.ttf',true);
			//$this->AddFont('DejaVu','B','DejaVuSans.ttf',true);
			$this->AddFont('DejaVu','U','DejaVuSans.ttf',true);
			$this->SetFont('DejaVu', '', 12);
			// Get the total page width
			$this->pageWidth = $this->GetPageWidth();
		}


		function Header()
			{
				$this->Image($this->image_path, 20, 10, 30); // Use the class member variable
				// Calculate the starting position (2/3 of the page width)
				$startX = $this->pageWidth * (2/3);
				// Set the column width (1/3 of the page width)
				$columnWidth = $this->pageWidth / 3;

				// Set the X position to start at 2/3 of the page width
				$this->SetX($startX);
				$this->SetFontSize(12);
				$this->Cell($columnWidth, 8, 'Artemis alfa d.o.o.', 0, 1, 'L');
				$this->SetFontSize(10);
				$this->SetX($startX);
				$this->Cell($startX, 5, 'Strojarska cesta 20, Zagreb', 0, 1, 'L');
				$this->SetX($startX);
				$this->Cell($startX, 5, 'OIB: 46110698761', 0, 1, 'L');
				$this->Ln(10);
			}


		function Footer()
			{           
				$this->SetY(-30);
				$this->SetFontSize(7);
				$this->Cell(10, 4, 'Artemis alfa d.o.o. za usluge, Strojarska cesta 20, Zagreb, OIB: 46110698761', 0, 1, 'L');
				$this->Cell(10, 4, 'IBAN: HR1524020061101123000, Erste&Steiermarkische Bank d.d.', 0, 1, 'L');
				$this->Cell(10, 4, 'Trgovačko društvo upisano je u sudski registar Trgovačkog suda u Zagrebu pod matičnim brojem subjekta 081469035.', 0, 1, 'L');
				$this->Cell(10, 4, 'Temeljni kapital: 20.000,00 kuna / 2.654,46 € uplaćen u cijelosti. Član uprave: Hanžek Tomislav.', 0, 1, 'L');
				$this->Cell(10, 4, 'Uvjeti poslovanja: https://barbeca.hr/uvjeti-poslovanja/', 0, 1, 'L');
				$this->Cell(0, 5, 'Stranica ' . $this->PageNo() . ' od {nb}', 0, 0, 'R');
			}
	}

	// Generate PDF
	$pdf = new PDF($image_path);
	$pdf->AliasNbPages();
	$pdf->AddPage();


	$pdf->SetFontSize(16);
	$pdf->Ln(10);
	$pdf->SetX($pdf->GetX() + $left_margin);
	// Use MultiCell for "OTPREMNICA:" to get its height
	$pdf->MultiCell(60, 10, 'OTPREMNICA:', 0, 'L');
	// Get the Y position after writing "OTPREMNICA:"
	$currentY = $pdf->GetY();
	// Move back up to align with the bottom of "OTPREMNICA:"
	$pdf->SetY($currentY-7);
	// Set X position for "123:"
	$pdf->SetX(55);
	// Set font size for "123:"
	$pdf->SetFontSize(10);
	// Write "123:" aligned to the bottom
	$pdf->Cell(70, 5, 'br. '.$broj_otpremnice, 0, 1, 'L');
	$pdf->Ln(2);

	$pdf->SetFontSize(8);
	$pdf->SetX($pdf->GetX() + $left_margin);
	$pdf->Cell(0, 5, 'Datum otpremnice: ' .$datum_otpremnice, 0, 1);
	$pdf->Ln(10);


	
	$pdf->SetFont('DejaVu', 'U', 11);
	$pdf->SetX($pdf->GetX() + $left_margin);
	$pdf->Cell(60, 5, 'Kupac:', 0, 0);
	$pdf->Cell(70, 5, 'Adresa dostave:', 0, 1);

	$pdf->SetFont('DejaVu', '', 9);


	$pdf->SetX($pdf->GetX() + $left_margin);
	$pdf->Cell(60, 5, 'OIB: '.$oib_tvrtke, 0, 0);
	$pdf->Cell(70, 5, $shipping_full_name, 0, 0);
	$pdf->Cell(60, 5, 'Broj narudžbe: '.$order_id, 0, 1);

	$pdf->SetX($pdf->GetX() + $left_margin);
	$pdf->Cell(60, 5, $ime_tvrtke, 0, 0);
	$pdf->Cell(70, 5, $stupac2_red2, 0, 0);
	#$pdf->Cell(60, 5, 'Datum narudžbe: '.$order_date, 0, 1);
	$pdf->Cell(60, 5, $dodatno_polje_1, 0, 1);

	$pdf->SetX($pdf->GetX() + $left_margin);
	$pdf->Cell(60, 5, $adresa_tvrtke, 0, 0);
	$pdf->Cell(70, 5, $stupac2_red3, 0, 0);
	#$pdf->Cell(60, 5, $dodatno_polje_1, 0, 1);
	$pdf->Cell(60, 5, $dodatno_polje_2, 0, 1);

	$pdf->SetX($pdf->GetX() + $left_margin);
	$pdf->Cell(60, 5, $grad_tvrtke, 0, 0);
	$pdf->Cell(70, 5, $stupac2_red4, 0, 1);
	#$pdf->Cell(60, 5, $dodatno_polje_2, 0, 1);
	$pdf->Ln(10);

	

	// Table header
	$pdf->SetFillColor(0, 0, 0);
	$pdf->SetTextColor(255, 255, 255);
	$pdf->SetX($pdf->GetX() + $left_margin);
	$pdf->Cell(25, 7, 'Artikl', 1, 0, 'C', true);
	$pdf->Cell(80, 7, 'Naziv artikla', 1, 0, 'C', true);
	$pdf->Cell(20, 7, 'Neto', 1, 0, 'C', true);
	$pdf->Cell(20, 7, 'Bruto', 1, 0, 'C', true);
	$pdf->Cell(25, 7, 'Tarifna ozn.', 1, 0, 'C', true);
	$pdf->Cell(15, 7, 'Kol.', 1, 1, 'C', true);

	// Reset text color
	$pdf->SetTextColor(0, 0, 0);

	// Order Items
	foreach ($items as $item) {
		//$product_id = $item->get_product_id();
		$product_name = $item->get_name();
		$quantity = $item->get_quantity();
		$product_sku = $item->get_product()->get_sku();
 

		$pdf->SetX($pdf->GetX() + $left_margin);
		$pdf->Cell(25, 7, $product_sku, 1, 0, 'C');
		$pdf->Cell(80, 7, $product_name, 1, 0, 'L');
		$pdf->Cell(20, 7, '', 1, 0, 'C'); // Neto - prazno
		$pdf->Cell(20, 7, '', 1, 0, 'C'); // Bruto - prazno
		$pdf->Cell(25, 7, '', 1, 0, 'C'); // Tarifna ozn. - prazno
		$pdf->Cell(15, 7, $quantity, 1, 1, 'C');
	}

	// Add empty rows if specified
	if ($dodatni_prazni_retci > 0) {
		for ($i = 0; $i < $dodatni_prazni_retci; $i++) {
			$pdf->SetX($pdf->GetX() + $left_margin);
			$pdf->Cell(25, 7, '', 1, 0, 'C');
			$pdf->Cell(80, 7, '', 1, 0, 'L');
			$pdf->Cell(20, 7, '', 1, 0, 'C'); // Neto - prazno
			$pdf->Cell(20, 7, '', 1, 0, 'C'); // Bruto - prazno
			$pdf->Cell(25, 7, '', 1, 0, 'C'); // Tarifna ozn. - prazno
			$pdf->Cell(15, 7, '', 1, 1, 'C');
		}
	}

	// Razmak između tablica
	$pdf->Ln(5);

	// Donja tablica - jedan red bez headera
	$pdf->SetX($pdf->GetX() + $left_margin);
	$pdf->Cell(25, 7, '', 0, 0, 'C');
	$pdf->Cell(80, 7, 'Ukupno', 0, 0, 'R');
	$pdf->Cell(20, 7, '', 1, 0, 'C');
	$pdf->Cell(20, 7, '', 1, 0, 'C');
	$pdf->Cell(25, 7, '', 1, 0, 'C');
	$pdf->Cell(15, 7, '', 1, 1, 'C');

	$pdf->Ln(20);


	$pdf->Cell(90, 5, '', 0, 0);
	$pdf->Cell(0, 5, 'Primatelj robe:  ___________________________________', 0, 1);

	$pdf->Ln(10);
	$pdf->Cell(90, 5, '', 0, 0);
	$pdf->Cell(0, 5, 'Isporučio:          ___________________________________', 0, 1);
  

 
	// Output the PDF to the browser
	//$pdf->Output('I', 'Otpremnica.pdf');

	 // Set headers for PDF output
	header('Content-Type: application/pdf');
	header('Content-Disposition: inline; filename="otpremnica_' . $order_id . '.pdf"');
	
	// Output the PDF directly to the browser
	$pdf->Output('otpremnica_' . $order_id . '.pdf', 'I');
	
	// Stop PHP execution after outputting the PDF
	exit;

}


function generiraj_racun_metro($data) {
    $image_path = plugin_dir_path(__FILE__) . 'images/logo.png';

    if (headers_sent()) {
        wp_die('Some output has already been sent. Unable to generate PDF.');
    }

    $order = $data['order'];
    if (!$order) {
        wp_die('Invalid order ID.');
    }

    $order_id = $data["order_id"];
    $broj_racuna = $data["broj_racuna"];
    $broj_otpremnice = $data["broj_otpremnice"];
    
    $datum_racuna = $data["datum_racuna"];
    $date = DateTime::createFromFormat('Y-m-d', $datum_racuna);
    $datum_racuna_formatted = $date ? $date->format('d.m.Y.') : 'Invalid date';

    $datum_dospijeca = $data["datum_dospijeca"];
    $date = DateTime::createFromFormat('Y-m-d', $datum_dospijeca);
    $datum_dospijeca_formatted = $date ? $date->format('d.m.Y.') : 'Invalid date';

    $datum_narudzbe = $data["datum_narudzbe"];
    $date = DateTime::createFromFormat('Y-m-d', $datum_narudzbe);
    $datum_narudzbe_formatted = $date ? $date->format('d.m.Y.') : 'Invalid date';

    $broj_metro_narudzbe = $data["broj_metro_narudzbe"];
    $broj_metro_dobavljaca = $data["broj_metro_dobavljaca"];

    $left_margin = 3;

    $company_name = $order->get_meta('Ime tvrtke');
    $company_oib = $order->get_meta('OIB tvrtke');
    $company_address = $order->get_meta('Adresa tvrtke');
    $company_city = $order->get_meta('Grad tvrtke');

    $shipping_name = $order->get_formatted_billing_full_name();
    $address1 = $order->get_shipping_address_1();
    $address2 = $order->get_shipping_address_2();
    $city = $order->get_shipping_city();
    $postcode = $order->get_shipping_postcode();
    $shipping_country = $order->get_shipping_country();

    $stupac2_red3 = $postcode . ", " . $city;
    $stupac2_red4 = "";
    if ($address2 !== "") {
        $stupac2_red4 = $stupac2_red3;
        $stupac2_red3 = $address2;
    }

    $obj_order_date = $order->get_date_created();
    $order_date = $obj_order_date ? $obj_order_date->format('d.m.Y.') : '';

    $items = $order->get_items();

    class PDF extends tFPDF {
        private $image_path;

        function __construct($image_path) {
            parent::__construct();
            $this->image_path = $image_path;
            $this->AddFont('DejaVu','','DejaVuSans.ttf',true);
            $this->AddFont('DejaVu','U','DejaVuSans.ttf',true);
            //$this->AddFont('DejaVu','B','DejaVuSans-Bold.ttf',true);
            $this->SetFont('DejaVu', '', 12);
            $this->pageWidth = $this->GetPageWidth();
        }

        function Header() {
            $this->Image($this->image_path, 20, 10, 30);
            $startX = $this->pageWidth * (2/3);
            $columnWidth = $this->pageWidth / 3;
            $this->SetX($startX);
            //$this->SetFontSize(12);
            $this->SetFont('DejaVu', '', 10); //b
            $this->Cell($columnWidth, 8, 'Artemis alfa d.o.o.', 0, 1, 'L');
            //$this->SetFontSize(10);
            $this->SetFont('DejaVu', '', 8);
            $this->SetX($startX);
            $this->Cell($startX, 5, 'Strojarska cesta 20, Zagreb', 0, 1, 'L');
            $this->SetX($startX);
            $this->Cell($startX, 5, 'OIB: 46110698761', 0, 1, 'L');
            $this->Ln(10);
        }

        function Footer() {
            $this->SetY(-30);
            $this->SetFontSize(7);
            $this->Cell(10, 4, 'Artemis alfa d.o.o. za usluge, Strojarska cesta 20, Zagreb, OIB: 46110698761', 0, 1, 'L');
            $this->Cell(10, 4, 'IBAN: HR1524020061101123000, Erste&Steiermarkische Bank d.d.', 0, 1, 'L');
            $this->Cell(10, 4, 'Trgovačko društvo upisano u sudski registar Trgovačkog suda u Zagrebu pod matičnim brojem subjekta 081469035.', 0, 1, 'L');
            $this->Cell(10, 4, 'Temeljni kapital: 20.000,00 kuna / 2.654,46 € uplaćen u cijelosti. Član uprave: Hanžek Tomislav.', 0, 1, 'L');
            $this->Cell(10, 4, 'Uvjeti poslovanja: https://barbeca.hr/uvjeti-poslovanja/', 0, 1, 'L');
            $this->Cell(0, 5, 'Stranica ' . $this->PageNo() . ' od {nb}', 0, 0, 'R');
        }
    }

    $pdf = new PDF($image_path);
    $pdf->AliasNbPages();
    $pdf->AddPage();

    //$pdf->SetFontSize(16);
    $pdf->SetFont('DejaVu', '', 16); //b
    $pdf->Ln(10);
    $pdf->SetX($pdf->GetX() + $left_margin);
    $pdf->MultiCell(60, 10, 'RAČUN R1', 0, 'L');
    $pdf->Ln(5);

    $pdf->SetFont('DejaVu', '', 8); //b
    $pdf->SetX($pdf->GetX() + $left_margin);
    $pdf->Cell(60, 5, 'Kupac:', 0, 0);
    $pdf->Cell(70, 5, 'Dostava na:', 0, 1);
    $pdf->SetFont('DejaVu', '', 8);
    $pdf->SetX($pdf->GetX() + $left_margin);
    $pdf->Cell(60, 5, 'METRO Cash & Carry, d.o.o.', 0, 0);
    //$pdf->Cell(70, 5, 'Veleprodajni centar Jankomir', 0, 0);
    $pdf->Cell(70, 5, $address1, 0, 0);
    $pdf->Cell(60, 5, 'Broj računa: ' . $broj_racuna, 0, 1);
    $pdf->SetX($pdf->GetX() + $left_margin);
    $pdf->Cell(60, 5, 'Jankomir 31', 0, 0);
    //$pdf->Cell(70, 5, 'Jankomir 31', 0, 0);
    $pdf->Cell(70, 5, $address2, 0, 0);
    $pdf->Cell(60, 5, 'Broj otpremnice: ' . $broj_otpremnice, 0, 1);
    $pdf->SetX($pdf->GetX() + $left_margin);
    $pdf->Cell(60, 5, 'Zagreb, 10000', 0, 0);
    //$pdf->Cell(70, 5, 'Zagreb, 10000', 0, 0);
    $pdf->Cell(70, 5, $city.', '.$postcode, 0, 0);
    $pdf->Cell(60, 5, 'Datum računa: ' . $datum_racuna_formatted, 0, 1);
    $pdf->SetX($pdf->GetX() + $left_margin);
    $pdf->Cell(60, 5, 'OIB: 38016445738', 0, 0);
    $pdf->Cell(70, 5, $shipping_country, 0, 0);
    $pdf->Cell(60, 5, 'Broj narudžbe: ' . $order_id, 0, 1);
    $pdf->SetX($pdf->GetX() + $left_margin);
    $pdf->Cell(60, 5, 'Tel. +38591 3444048', 0, 0);
    $pdf->Cell(70, 5, '', 0, 0);
    $pdf->Cell(60, 5, 'Datum narudžbe: ' . $datum_narudzbe_formatted, 0, 1);
    $pdf->SetX($pdf->GetX() + $left_margin);
    $pdf->Cell(60, 5, '', 0, 0);
    $pdf->Cell(70, 5, '', 0, 0);
    $pdf->Cell(60, 5, 'Datum dospijeća: ' . $datum_dospijeca_formatted, 0, 1);
    $pdf->SetX($pdf->GetX() + $left_margin);
    $pdf->Cell(60, 5, '', 0, 0);
    $pdf->Cell(70, 5, '', 0, 0);
    $pdf->Cell(60, 5, 'Broj Metro dobavljača: ' . $broj_metro_dobavljaca, 0, 1);
    $pdf->SetX($pdf->GetX() + $left_margin);
    $pdf->Cell(60, 5, '', 0, 0);
    $pdf->Cell(70, 5, '', 0, 0);
    $pdf->Cell(60, 5, 'Broj Metro narudžbe: ' . $broj_metro_narudzbe, 0, 1);

    $pdf->Ln(10);

	$pdf->SetFillColor(0, 0, 0);
	$pdf->SetTextColor(255, 255, 255);
	$pdf->SetFontSize(7);

	$headers = [
		['Metro šifra', 'robe', 20],
		['Naziv artikla', ' ', 60],
		['Kol.', ' ', 10],
		['Jed. cijena', '(bez PDV-a)', 20],
		['Jed. cijena', '(s PDV-om)', 20],
		['Ukupna cijena', '(bez PDV-a)', 25],
		['Ukupno', 'PDV', 15],
		['Ukupna cijena', '(s PDV-om)', 20],
	];

	$startX = $pdf->GetX() + $left_margin;
	$startY = $pdf->GetY();
	$rowHeight = 4;

	foreach ($headers as $header) {
		list($line1, $line2, $width) = $header;
		$pdf->SetXY($startX, $startY);
		$pdf->MultiCell($width, $rowHeight, $line1 . "\n" . $line2, 1, 'C', true);
		$startX += $width;
	}

	$pdf->SetTextColor(0, 0, 0);


    foreach ($items as $item) {
        $product = $item->get_product();
        $sku_metro = $product ? $product->get_meta('metro_sifra_robe') : '';
        $name = $item->get_name();
        $qty = $item->get_quantity();
        $price_net = $item->get_subtotal() / $qty;

        //custom_log("this is item", $item);
        //custom_log("this is subtotal", $item->get_subtotal());

        
        $price_gross = ($price_net * 1.25);
        $price_gross = round($price_gross, 2);

        $line_net = $item->get_subtotal();
        $line_tax = $item->get_subtotal_tax();

        $line_gross = ($line_net * 1.25);
        $line_gross = round($line_gross, 2);

        $pdf->SetFontSize(7);
        $pdf->SetX($pdf->GetX() + $left_margin);
        $border = 'B'; // Only bottom border
        $pdf->Cell(25, 6, $sku_metro, $border);
        $pdf->Cell(60, 6, $name, $border);
        $pdf->Cell(5, 6, $qty, $border, 0, 'C');
        $pdf->Cell(20, 6, number_format($price_net, 2) . ' €', $border, 0, 'R');
        $pdf->Cell(20, 6, number_format($price_gross, 2) . ' €', $border, 0, 'R');
        $pdf->Cell(25, 6, number_format($line_net, 2) . ' €', $border, 0, 'R');
        $pdf->Cell(15, 6, number_format($line_tax, 2) . ' €', $border, 0, 'R');
        $pdf->Cell(20, 6, number_format($line_gross, 2) . ' €', $border, 1, 'R');
    }

    $pdf->Ln(5);
    $pdf->SetFont('DejaVu', '', 8);

    $margina_za_total = 115;
    // Move X to align to the last few columns
    $pdf->SetX($pdf->GetX() + $margina_za_total);

    // Totals – adjust values based on calculations
    $subtotal = number_format($order->get_subtotal(), 2);
    $total_tax = number_format($order->get_total_tax(), 2);
    $total = number_format($order->get_total(), 2);

    // First block
    $pdf->Cell(30, 6, 'Ukupno bez PDV-a', 0, 0);
    $pdf->Cell(35, 6, $subtotal . ' €', 0, 1, 'R');

    $pdf->SetX($pdf->GetX() + $margina_za_total);
    $pdf->Cell(30, 6, 'Ukupno PDV', 0, 0);
    $pdf->Cell(35, 6, $total_tax . ' €', 0, 1, 'R');

    $pdf->SetX($pdf->GetX() + $margina_za_total);
    $pdf->Cell(30, 6, 'Ukupno', 0, 0);
    $pdf->Cell(35, 6, $total . ' €', 0, 1, 'R');

    $pdf->Ln(5);
    $pdf->SetFont('DejaVu', 'U', 9); //b

    // Second block – bolded totals
    $pdf->SetX($pdf->GetX() + $margina_za_total);
    $pdf->Cell(30, 6, 'Sveukupno bez PDV-a', 0, 0);
    $pdf->Cell(35, 6, $subtotal . ' €', 0, 1, 'R');

    $pdf->SetX($pdf->GetX() + $margina_za_total);
    $pdf->Cell(30, 6, 'Sveukupno PDV', 0, 0);
    $pdf->Cell(35, 6, $total_tax . ' €', 0, 1, 'R');

    $pdf->SetX($pdf->GetX() + $margina_za_total);
    $pdf->Cell(30, 6, 'Sveukupno', 0, 0);
    $pdf->Cell(35, 6, $total . ' €', 0, 1, 'R');

    $pdf->SetFont('DejaVu', '', 8);

    global $wpdb;
	$fiskalizacija_res = array();

	$sql_query = $wpdb->prepare("SELECT * FROM wp_fiskal WHERE br_racuna=%s AND br_narudzbe=%s", $broj_racuna, $order_id);
	$res = $wpdb->get_results($sql_query);
    if($res) {
        $total_value_cent = $res[0]->iznos_racuna_cent;
        $jir = $res[0]->jir;
        $fis_date_time = $res[0]->datum_vrijeme;
        $fis_date = DateTime::createFromFormat('d.m.Y\TH:i:s', $fis_date_time);

        // Format it as desired: YYYYMMDD_HHMM
        $fis_formatted_date = $fis_date->format('Ymd_Hi');

        $url_qrcode = 'https://api.sensorium.hr/fiskalqrcode?page=https://porezna.gov.hr/rn&jir='.$jir.'&datv='.$fis_formatted_date.'&izn='.$total_value_cent;

        $qrcode_string = '<img src="'.$url_qrcode.'" alt="qrcode" width="120" height="120";>';

        // Check if sensorium api is available
		$headers = @get_headers($url_qrcode);
		if ($headers && strpos($headers[0], '200')) {
            // Preuzmi QR kod kao lokalni privremeni file (jer FPDF ne može direktno koristiti URL)
            $qr_temp_file = tempnam(sys_get_temp_dir(), 'qr_') . '.png';
            file_put_contents($qr_temp_file, file_get_contents($url_qrcode));

            // Dodaj QR kod u PDF
            $pdf->Image($qr_temp_file, $pdf->GetX() + 5, $pdf->GetY(), 30, 30); // Pozicija i veličina QR-a
            $pdf->Ln(32); // Pomak ispod QR koda

            // Obriši privremeni file
            unlink($qr_temp_file);
        }

		$pdf->SetFont('DejaVu', '', 8);
        $pdf->Cell(0, 5, 'ZKI: ' . $res[0]->zki, 0, 1);
        $pdf->Cell(0, 5, 'JIR: ' . $res[0]->jir, 0, 1);
    }

    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="racun_metro_' . $order_id . '.pdf"');
    $pdf->Output('racun_metro_' . $order_id . '.pdf', 'I');
    exit;
}


function generiraj_racun_pevex($data) {
    $image_path = plugin_dir_path(__FILE__) . 'images/logo.png';

    if (headers_sent()) {
        wp_die('Some output has already been sent. Unable to generate PDF.');
    }

    $order = $data['order'];
    if (!$order) {
        wp_die('Invalid order ID.');
    }

    $order_id = $data["order_id"];
    $broj_racuna = $data["broj_racuna"];
    $broj_otpremnice = $data["broj_otpremnice"];
    
    $datum_racuna = $data["datum_racuna"];
    $date = DateTime::createFromFormat('Y-m-d', $datum_racuna);
    $datum_racuna_formatted = $date ? $date->format('d.m.Y.') : 'Invalid date';

    $datum_narudzbe = $data["datum_narudzbe"];
    $date = DateTime::createFromFormat('Y-m-d', $datum_narudzbe);
    $datum_narudzbe_formatted = $date ? $date->format('d.m.Y.') : 'Invalid date';

    $broj_pevex_narudzbe = $data["broj_pevex_narudzbe"];
    $valuta_placanja = $data["valuta_placanja"];

    $left_margin = 3;

    $company_name = $order->get_meta('Ime tvrtke');
    $company_oib = $order->get_meta('OIB tvrtke');
    $company_address = $order->get_meta('Adresa tvrtke');
    $company_city = $order->get_meta('Grad tvrtke');

    $shipping_name = $order->get_formatted_billing_full_name();
    $address1 = $order->get_shipping_address_1();
    $address2 = $order->get_shipping_address_2();
    $city = $order->get_shipping_city();
    $postcode = $order->get_shipping_postcode();
    $shipping_country = $order->get_shipping_country();

    $stupac2_red3 = $postcode . ", " . $city;
    $stupac2_red4 = "";
    if ($address2 !== "") {
        $stupac2_red4 = $stupac2_red3;
        $stupac2_red3 = $address2;
    }

    $obj_order_date = $order->get_date_created();
    $order_date = $obj_order_date ? $obj_order_date->format('d.m.Y.') : '';

    $items = $order->get_items();

    class PDF extends tFPDF {
        private $image_path;

        function __construct($image_path) {
            parent::__construct();
            $this->image_path = $image_path;
            $this->AddFont('DejaVu','','DejaVuSans.ttf',true);
            $this->AddFont('DejaVu','U','DejaVuSans.ttf',true);
            //$this->AddFont('DejaVu','B','DejaVuSans-Bold.ttf',true);
            $this->SetFont('DejaVu', '', 12);
            $this->pageWidth = $this->GetPageWidth();
        }

        function Header() {
            $this->Image($this->image_path, 20, 10, 30);
            $startX = $this->pageWidth * (2/3);
            $columnWidth = $this->pageWidth / 3;
            $this->SetX($startX);
            //$this->SetFontSize(12);
            $this->SetFont('DejaVu', '', 10); //b
            $this->Cell($columnWidth, 8, 'Artemis alfa d.o.o.', 0, 1, 'L');
            //$this->SetFontSize(10);
            $this->SetFont('DejaVu', '', 8);
            $this->SetX($startX);
            $this->Cell($startX, 5, 'Strojarska cesta 20, Zagreb', 0, 1, 'L');
            $this->SetX($startX);
            $this->Cell($startX, 5, 'OIB: 46110698761', 0, 1, 'L');
            $this->Ln(10);
        }

        function Footer() {
            $this->SetY(-30);
            $this->SetFontSize(7);
            $this->Cell(10, 4, 'Artemis alfa d.o.o. za usluge, Strojarska cesta 20, Zagreb, OIB: 46110698761', 0, 1, 'L');
            $this->Cell(10, 4, 'IBAN: HR1524020061101123000, Erste&Steiermarkische Bank d.d.', 0, 1, 'L');
            $this->Cell(10, 4, 'Trgovačko društvo upisano u sudski registar Trgovačkog suda u Zagrebu pod matičnim brojem subjekta 081469035.', 0, 1, 'L');
            $this->Cell(10, 4, 'Temeljni kapital: 20.000,00 kuna / 2.654,46 € uplaćen u cijelosti. Član uprave: Hanžek Tomislav.', 0, 1, 'L');
            $this->Cell(10, 4, 'Uvjeti poslovanja: https://barbeca.hr/uvjeti-poslovanja/', 0, 1, 'L');
            $this->Cell(0, 5, 'Stranica ' . $this->PageNo() . ' od {nb}', 0, 0, 'R');
        }
    }

    $pdf = new PDF($image_path);
    $pdf->AliasNbPages();
    $pdf->AddPage();

    //$pdf->SetFontSize(16);
    $pdf->SetFont('DejaVu', '', 16); //b
    $pdf->Ln(10);
    $pdf->SetX($pdf->GetX() + $left_margin);
    $pdf->MultiCell(60, 10, 'RAČUN R1', 0, 'L');
    $pdf->Ln(5);

    $pdf->SetFont('DejaVu', '', 8); //b
    $pdf->SetX($pdf->GetX() + $left_margin);
    $pdf->Cell(60, 5, 'Kupac:', 0, 0);
    $pdf->Cell(70, 5, 'Dostava na:', 0, 1);
    $pdf->SetFont('DejaVu', '', 8);
    $pdf->SetX($pdf->GetX() + $left_margin);
    $pdf->Cell(60, 5, 'PEVEX d.d.', 0, 0);
    // $pdf->Cell(70, 5, 'Prodajni centar Osijek', 0, 0);
    $pdf->Cell(70, 5, $address1, 0, 0);
    $pdf->Cell(60, 5, 'Broj računa: ' . $broj_racuna, 0, 1);
    $pdf->SetX($pdf->GetX() + $left_margin);
    $pdf->Cell(60, 5, 'Savska cesta 84', 0, 0);
    // $pdf->Cell(70, 5, 'Kneza Trpimira 24', 0, 0);
    $pdf->Cell(70, 5, $address2, 0, 0);
    $pdf->Cell(60, 5, 'Broj otpremnice: ' . $broj_otpremnice, 0, 1);
    $pdf->SetX($pdf->GetX() + $left_margin);
    $pdf->Cell(60, 5, 'Sesvete, 10360', 0, 0);
    // $pdf->Cell(70, 5, '31000 Osijek', 0, 0);
    $pdf->Cell(70, 5, $city.', '.$postcode, 0, 0);
    $pdf->Cell(60, 5, 'Datum računa: ' . $datum_racuna_formatted, 0, 1);
    $pdf->SetX($pdf->GetX() + $left_margin);
    $pdf->Cell(60, 5, 'OIB: 73660371074', 0, 0);
    $pdf->Cell(70, 5, $shipping_country, 0, 0);
    $pdf->Cell(60, 5, 'Broj narudžbe: ' . $order_id, 0, 1);
    $pdf->SetX($pdf->GetX() + $left_margin);
    $pdf->Cell(60, 5, '', 0, 0);
    $pdf->Cell(70, 5, '', 0, 0);
    $pdf->Cell(60, 5, 'Datum narudžbe: ' . $datum_narudzbe_formatted, 0, 1);
    $pdf->SetX($pdf->GetX() + $left_margin);
    $pdf->Cell(60, 5, '', 0, 0);
    $pdf->Cell(70, 5, '', 0, 0);
    $pdf->Cell(60, 5, 'Broj Pevex narudžbe: ' . $broj_pevex_narudzbe, 0, 1);
    $pdf->SetX($pdf->GetX() + $left_margin);
    $pdf->Cell(60, 5, '', 0, 0);
    $pdf->Cell(70, 5, '', 0, 0);
    $pdf->Cell(60, 5, 'Valuta plaćanja: ' . $valuta_placanja, 0, 1);

    $pdf->Ln(10);

	$pdf->SetFillColor(0, 0, 0);
	$pdf->SetTextColor(255, 255, 255);
	$pdf->SetFontSize(7);

	$headers = [
		['Pevex šifra', 'robe', 20],
		['Naziv artikla', ' ', 60],
		['Kol.', ' ', 10],
		['Jed. cijena', '(bez PDV-a)', 20],
		['Jed. cijena', '(s PDV-om)', 20],
		['Ukupna cijena', '(bez PDV-a)', 25],
		['Ukupno', 'PDV', 15],
		['Ukupna cijena', '(s PDV-om)', 20],
	];

	$startX = $pdf->GetX() + $left_margin;
	$startY = $pdf->GetY();
	$rowHeight = 4;

	foreach ($headers as $header) {
		list($line1, $line2, $width) = $header;
		$pdf->SetXY($startX, $startY);
		$pdf->MultiCell($width, $rowHeight, $line1 . "\n" . $line2, 1, 'C', true);
		$startX += $width;
	}

	$pdf->SetTextColor(0, 0, 0);


    foreach ($items as $item) {
        $product = $item->get_product();
        $sku_pevex = $product ? $product->get_meta('pevex_sifra_robe') : '';
        $name = $item->get_name();
        $qty = $item->get_quantity();
        $price_net = $item->get_subtotal() / $qty;
        
        $price_gross = ($price_net * 1.25);
        $price_gross = round($price_gross, 2);

        $line_net = $item->get_subtotal();
        $line_tax = $item->get_subtotal_tax();

        $line_gross = ($line_net * 1.25);
        $line_gross = round($line_gross, 2);

        $pdf->SetFontSize(7);
        $pdf->SetX($pdf->GetX() + $left_margin);
        $border = 'B'; // Only bottom border
        $pdf->Cell(25, 6, $sku_pevex, $border);
        $pdf->Cell(60, 6, $name, $border);
        $pdf->Cell(5, 6, $qty, $border, 0, 'L');
        $pdf->Cell(20, 6, number_format($price_net, 2) . ' €', $border, 0, 'R');
        $pdf->Cell(20, 6, number_format($price_gross, 2) . ' €', $border, 0, 'R');
        $pdf->Cell(25, 6, number_format($line_net, 2) . ' €', $border, 0, 'R');
        $pdf->Cell(15, 6, number_format($line_tax, 2) . ' €', $border, 0, 'R');
        $pdf->Cell(20, 6, number_format($line_gross, 2) . ' €', $border, 1, 'R');
    }

    $pdf->Ln(5);
    $pdf->SetFont('DejaVu', '', 8);

    $margina_za_total = 115;
    // Move X to align to the last few columns
    $pdf->SetX($pdf->GetX() + $margina_za_total);

    // Totals – adjust values based on calculations
    $subtotal = number_format($order->get_subtotal(), 2);
    $total_tax = number_format($order->get_total_tax(), 2);
    $total = number_format($order->get_total(), 2);

    // First block
    $pdf->Cell(30, 6, 'Ukupno bez PDV-a', 0, 0);
    $pdf->Cell(35, 6, $subtotal . ' €', 0, 1, 'R');

    $pdf->SetX($pdf->GetX() + $margina_za_total);
    $pdf->Cell(30, 6, 'Ukupno PDV', 0, 0);
    $pdf->Cell(35, 6, $total_tax . ' €', 0, 1, 'R');

    $pdf->SetX($pdf->GetX() + $margina_za_total);
    $pdf->Cell(30, 6, 'Ukupno', 0, 0);
    $pdf->Cell(35, 6, $total . ' €', 0, 1, 'R');

    $pdf->Ln(5);
    $pdf->SetFont('DejaVu', 'U', 9); //b

    // Second block – bolded totals
    $pdf->SetX($pdf->GetX() + $margina_za_total);
    $pdf->Cell(30, 6, 'Sveukupno bez PDV-a', 0, 0);
    $pdf->Cell(35, 6, $subtotal . ' €', 0, 1, 'R');

    $pdf->SetX($pdf->GetX() + $margina_za_total);
    $pdf->Cell(30, 6, 'Sveukupno PDV', 0, 0);
    $pdf->Cell(35, 6, $total_tax . ' €', 0, 1, 'R');

    $pdf->SetX($pdf->GetX() + $margina_za_total);
    $pdf->Cell(30, 6, 'Sveukupno', 0, 0);
    $pdf->Cell(35, 6, $total . ' €', 0, 1, 'R');

    $pdf->SetFont('DejaVu', '', 8);

    global $wpdb;
	$fiskalizacija_res = array();

	$sql_query = $wpdb->prepare("SELECT * FROM wp_fiskal WHERE br_racuna=%s AND br_narudzbe=%s", $broj_racuna, $order_id);
	$res = $wpdb->get_results($sql_query);
    if($res) {
        $total_value_cent = $res[0]->iznos_racuna_cent;
        $jir = $res[0]->jir;
        $fis_date_time = $res[0]->datum_vrijeme;
        $fis_date = DateTime::createFromFormat('d.m.Y\TH:i:s', $fis_date_time);

        // Format it as desired: YYYYMMDD_HHMM
        $fis_formatted_date = $fis_date->format('Ymd_Hi');

        $url_qrcode = 'https://api.sensorium.hr/fiskalqrcode?page=https://porezna.gov.hr/rn&jir='.$jir.'&datv='.$fis_formatted_date.'&izn='.$total_value_cent;

        $qrcode_string = '<img src="'.$url_qrcode.'" alt="qrcode" width="120" height="120";>';

        // Check if sensorium api is available
		$headers = @get_headers($url_qrcode);
		if ($headers && strpos($headers[0], '200')) {
            // Preuzmi QR kod kao lokalni privremeni file (jer FPDF ne može direktno koristiti URL)
            $qr_temp_file = tempnam(sys_get_temp_dir(), 'qr_') . '.png';
            file_put_contents($qr_temp_file, file_get_contents($url_qrcode));

            // Dodaj QR kod u PDF
            $pdf->Image($qr_temp_file, $pdf->GetX() + 5, $pdf->GetY(), 30, 30); // Pozicija i veličina QR-a
            $pdf->Ln(32); // Pomak ispod QR koda

            // Obriši privremeni file
            unlink($qr_temp_file);
        }

		$pdf->SetFont('DejaVu', '', 8);
        $pdf->Cell(0, 5, 'ZKI: ' . $res[0]->zki, 0, 1);
        $pdf->Cell(0, 5, 'JIR: ' . $res[0]->jir, 0, 1);
    }




    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="racun_metro_' . $order_id . '.pdf"');
    $pdf->Output('racun_metro_' . $order_id . '.pdf', 'I');
    exit;
}



