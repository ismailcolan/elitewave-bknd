GCN expense screen — GST tracking + summary card redesign (2026-10-21)

Changes rolled back by these files:
- GCN Summary uses list-style meta + KPI cards (no freight line)
- Expense lines: GST % dropdown + calculated GST amount (tracking only)
- gst_percent column on gcn_expense_lines (column is not dropped on rollback)

Restore from web/ directory:
  cp -a .rollback-expense/expense_gcn.php expense_gcn.php
  cp -a .rollback-expense/expense_gcn_helpers.php include/expense_gcn_helpers.php
