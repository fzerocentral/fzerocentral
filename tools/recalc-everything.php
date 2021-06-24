<?php

require_once __DIR__ . '/../common.php';

// Recalculate every ladder totals, AF, and SRPR.

foreach ([1, 2, 3, 4, 5, 6, 7, 8, 11, 12, 13, 14, 15, 16, 17, 18] as $ladder_id) {
  recalc_ladder_totals($ladder_id);
  recalc_af($ladder_id);
  recalc_srpr($ladder_id);
}

recalc_af_totals();
recalc_srpr_totals();
