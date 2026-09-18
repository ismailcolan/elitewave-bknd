<div class="ew-v2-modal-backdrop ew-delete-modal-backdrop" id="ewDeleteModal">
	<div class="ew-v2-modal ew-delete-modal" role="dialog" aria-labelledby="ewDeleteModalTitle" aria-modal="true">
		<div class="ew-v2-modal-head">
			<h3 id="ewDeleteModalTitle">Delete record</h3>
			<button type="button" class="ew-v2-modal-close" data-ew-v2-close aria-label="Close">&times;</button>
		</div>
		<div class="ew-v2-modal-body">
			<div class="ew-delete-modal__icon" aria-hidden="true"><i class="fa fa-trash-o"></i></div>
			<p class="ew-delete-modal__text" id="ewDeleteModalMessage">Do you want to delete this record? This action cannot be undone.</p>
		</div>
		<div class="ew-v2-modal-foot">
			<button type="button" class="ew-delete-modal__cancel" data-ew-v2-close>Cancel</button>
			<button type="button" class="ew-delete-modal__confirm" id="ewDeleteConfirmBtn">Delete</button>
		</div>
	</div>
	<button type="button" class="btn-confirm-delete ew-delete-compat" id="ewDeleteCompatBtn" tabindex="-1" aria-hidden="true"></button>
</div>

<div class="ew-v2-modal-backdrop" id="ewConfirmModal">
	<div class="ew-v2-modal ew-confirm-modal" role="dialog" aria-labelledby="ewConfirmModalTitle" aria-modal="true">
		<div class="ew-v2-modal-head">
			<h3 id="ewConfirmModalTitle">Please confirm</h3>
			<button type="button" class="ew-v2-modal-close" data-ew-v2-close aria-label="Close">&times;</button>
		</div>
		<div class="ew-v2-modal-body">
			<p class="ew-confirm-modal__text" id="ewConfirmModalMessage">Are you sure you want to continue?</p>
		</div>
		<div class="ew-v2-modal-foot">
			<button type="button" class="btn btn-default-outline" id="ewConfirmCancelBtn" data-ew-v2-close>Cancel</button>
			<button type="button" class="btn btn-primary" id="ewConfirmOkBtn">OK</button>
		</div>
	</div>
</div>
