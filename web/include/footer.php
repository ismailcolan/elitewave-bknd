</div><!-- /.main-content -->

<?php
if (file_exists(__DIR__ . '/ew_delete_modal.php')) {
	include __DIR__ . '/ew_delete_modal.php';
}
if (file_exists(__DIR__ . '/ew_cancel_modal.php')) {
	include __DIR__ . '/ew_cancel_modal.php';
}
?>

<footer class="ew-footer">
  <div class="ew-footer-brand" aria-hidden="true"><span></span><span></span></div>
  <div class="ew-footer-bar">
    <a class="ew-footer-chip ew-footer-left" href="mailto:athar@elitewave360.in"><span class="ew-footer-ico"><i class="fa fa-envelope" aria-hidden="true"></i></span> athar@elitewave360.in</a>
    <div class="ew-footer-center">
      <p>&copy; <?php echo date("Y"); ?> <a href="dashboard.php">Elite Wave 360</a>. All Rights Reserved.</p>
      <span class="ew-footer-tag">Expectations Delivered</span>
    </div>
    <a class="ew-footer-chip ew-footer-right" href="tel:+919382307611"><span class="ew-footer-ico"><i class="fa fa-phone" aria-hidden="true"></i></span> 9382307611</a>
  </div>
</footer>
