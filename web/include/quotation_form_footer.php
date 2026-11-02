<?php
/**
 * Quotation form footer (single / all modes / consignor multi-dest).
 * Expects: $editable, $form_editable, $status, $id, $master
 */
require_once __DIR__ . '/quotation_functions.php';

$qid = (int) ($id ?? 0);
$qno = htmlspecialchars($master['quote_no'] ?? '', ENT_QUOTES, 'UTF-8');
$st = (string) ($status ?? '');

$approve_title = 'Emails the PDF to the customer address on this form (copy to info@elitewave360.in).';

$can_cancel = !in_array($st, array('converted', 'cancelled'), true);
?>
<div class="ew-form-footer ew-invoice-form-footer ew-quote-footer-clean">
	<div class="ew-quote-footer-toolbar">
		<div class="ew-quote-footer-start">
			<?php if ($can_cancel) { ?>
				<button type="button" class="ew-btn-v2 ew-btn-v2-outline ew-quote-btn-muted btn-quotation-workflow" data-action="cancel">Cancel</button>
			<?php } ?>
		</div>
		<div class="ew-quote-footer-end">
			<?php if ($editable) { ?>
				<button type="button" class="ew-btn-v2 ew-btn-v2-outline btn-quotation-action" data-action="save_draft">Save draft</button>
				<button type="button" class="ew-btn-v2 ew-btn-v2-outline" id="btn_quotation_preview"><i class="fa fa-eye"></i> Preview</button>
				<button type="button" class="ew-btn-v2 ew-btn-v2-primary btn-quotation-action" data-action="submit">Submit for approval</button>
			<?php } elseif ($form_editable) { ?>
				<button type="button" class="ew-btn-v2 ew-btn-v2-outline btn-quotation-action" data-action="save_as_draft">Save draft</button>
				<button type="button" class="ew-btn-v2 ew-btn-v2-outline btn-quotation-action" data-action="save_draft">Save changes</button>
				<button type="button" class="ew-btn-v2 ew-btn-v2-outline" id="btn_quotation_preview"><i class="fa fa-eye"></i> Preview</button>
			<?php } else { ?>
				<button type="button" class="ew-btn-v2 ew-btn-v2-outline" id="btn_quotation_preview"><i class="fa fa-eye"></i> Preview</button>
			<?php } ?>

			<?php if ($st === 'pending_approval') { ?>
				<span class="ew-quote-footer-sep" aria-hidden="true"></span>
				<button type="button" class="ew-btn-v2 ew-btn-v2-outline btn-quotation-workflow" data-action="reject">Reject</button>
				<button type="button" class="ew-btn-v2 ew-btn-v2-primary btn-quotation-workflow" data-action="approve" title="<?php echo htmlspecialchars($approve_title, ENT_QUOTES, 'UTF-8'); ?>">Approve &amp; email</button>
			<?php } ?>

			<?php if ($st === 'approved') { ?>
				<span class="ew-quote-footer-sep" aria-hidden="true"></span>
				<button type="button" class="ew-btn-v2 ew-btn-v2-outline btn-quotation-workflow" data-action="mark_sent">Mark sent</button>
				<button type="button" class="ew-btn-v2 ew-btn-v2-outline btn-quotation-workflow" data-action="resend_email">Resend email</button>
			<?php } elseif (in_array($st, array('sent', 'customer_confirmed'), true)) { ?>
				<span class="ew-quote-footer-sep" aria-hidden="true"></span>
				<button type="button" class="ew-btn-v2 ew-btn-v2-outline btn-quotation-workflow" data-action="resend_email">Resend email</button>
			<?php } ?>

			<?php if (quotation_can_customer_approve($st) && $qid > 0) { ?>
				<button type="button" class="ew-btn-v2 ew-btn-v2-primary btn-customer-approve" data-id="<?php echo $qid; ?>" data-quote="<?php echo $qno; ?>">Upload customer reply</button>
			<?php } ?>
		</div>
	</div>
</div>
