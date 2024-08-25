<?php
   require_once(SB.'libraries/tcpdf/tcpdf.php');
   
   $pdf = new TCPDF();
   $pdf->AddPage();
   $pdf->Write(0, 'TCPDF berhasil diinstal di SLiMS!');
   $pdf->Output('test.pdf', 'I');
   ?>