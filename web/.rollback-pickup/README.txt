Pickup customer + delivered consignor edit rollback (2026-10-21)

These files are the booking form state BEFORE:
- Pickup customer on consignor
- Consignor editable on delivered (billing-only) GCNs

Ship-to customer and delivered consignee edit are unchanged in these copies.

Restore from web/ directory:
  cp -a .rollback-pickup/transactions.php transactions.php
  cp -a .rollback-pickup/save_details.php save_details.php
  cp -a .rollback-pickup/function.php include/function.php

Note: pickup_client_id may already exist on transaction tables; rollback does not drop that column.
