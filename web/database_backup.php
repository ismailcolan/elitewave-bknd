<?php
require_once __DIR__ . '/include/connect.php';
require_once __DIR__ . '/include/function.php';
require_once __DIR__ . '/include/db_backup_helpers.php';

if (!ew_db_backup_require_admin()) {
    echo '<script>location.href="dashboard.php";</script>';
    exit;
}

$backups = ew_db_backup_list();
$backupCount = count($backups);
?>
<!DOCTYPE html>
<html>
<head>
    <?php include __DIR__ . '/include/title.php'; ?>
    <?php include __DIR__ . '/include/css_js.php'; ?>
    <link href="stylesheets/ew-pages-v2.css?v=20261005dbbackup" rel="stylesheet" type="text/css" />
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
</head>
<body class="page-header-fixed bg-1">
<div class="modal-shiftfix">
    <div class="navbar navbar-fixed-top scroll-hide">
        <?php require_once __DIR__ . '/include/header.php'; require_once __DIR__ . '/include/menu.php'; ?>
    </div>
    <div class="container-fluid main-content new_dpt_bottom">
        <div class="row">
            <div class="col-md-12">
                <div class="ew-page-v2 ew-db-backup-page">
                    <div class="ew-page-head">
                        <div class="ew-page-head-left">
                            <h1 class="ew-page-title">Database Backup</h1>
                        </div>
                    </div>

                    <div class="ew-card ew-erp-list ew-db-backup-card">
                        <div class="ew-card-toolbar">
                            <div>
                                <h2>Database Backup</h2>
                                <p class="ew-db-backup-count"><span id="ewDbBackupCount"><?php echo (int) $backupCount; ?></span> Backups</p>
                            </div>
                            <div class="ew-toolbar-right">
                                <?php if ($backupCount > 0) { ?>
                                <a href="database_backup_download.php?all=1" class="ew-btn-v2 ew-btn-v2-outline" id="ewDbBackupAllBtn">
                                    <i class="fa fa-cloud-download"></i> Download all
                                </a>
                                <?php } ?>
                            </div>
                        </div>

                        <div class="ew-table-wrap widget-content padded clearfix">
                            <table class="table table-bordered table-striped ew-db-backup-table" id="ew_db_backup_table">
                                <thead>
                                <tr>
                                    <th class="ew-db-backup-date-col text-center" style="width:18%">Database Backup</th>
                                    <th style="width:22%">Description</th>
                                    <th style="width:12%">Size</th>
                                    <th class="text-center ew-db-backup-fromto-col" style="width:14%">From</th>
                                    <th class="text-center ew-db-backup-fromto-col" style="width:14%">To</th>
                                    <th class="sorting_disabled text-center" style="width:10%">Action</th>
                                </tr>
                                </thead>
                                <tbody id="ewDbBackupBody">
                                <?php if (!$backups) { ?>
                                    <tr class="ew-db-backup-empty">
                                        <td colspan="6" class="text-center text-muted">No backups yet. The daily job runs at 11:30 AM IST.</td>
                                    </tr>
                                <?php } else { ?>
                                    <?php foreach ($backups as $row) { ?>
                                        <tr data-date="<?php echo htmlspecialchars($row['date']); ?>">
                                            <td class="ew-db-backup-date-col text-center col-center">
                                                <span class="ew-db-backup-date-inner">
                                                    <span class="ew-db-backup-date-text ew-db-backup-date-text--primary"><?php echo htmlspecialchars($row['label']); ?></span>
                                                    <span class="ew-db-backup-dot-slot"><?php if (!empty($row['is_latest'])) { ?><span class="ew-db-backup-dot ew-db-backup-dot--live" title="Latest backup"></span><?php } ?></span>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($row['description']); ?></td>
                                            <td><?php echo htmlspecialchars($row['size_label']); ?></td>
                                            <td class="text-center ew-db-backup-fromto-col"><span class="ew-db-backup-date-text"><?php echo htmlspecialchars($row['from']); ?></span></td>
                                            <td class="text-center ew-db-backup-fromto-col"><span class="ew-db-backup-date-text"><?php echo htmlspecialchars($row['to']); ?></span></td>
                                            <td class="text-center">
                                                <a class="ew-db-backup-dl table-actions" title="Download"
                                                   href="database_backup_download.php?date=<?php echo urlencode($row['date']); ?>">
                                                    <i class="fa fa-cloud-download"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                <?php } ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="ew-table-footer">
                            <div class="ew-table-footer-left ew-dt-footer-left"></div>
                            <div class="ew-dt-footer-right ew-pagination"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
(function ($) {
  var dbBackupDt = null;
  var $backupTable = $('#ew_db_backup_table');

  function isBackupDtActive() {
    var el = $backupTable[0];
    if (!el || !$.fn.dataTable) {
      return false;
    }
    if ($.fn.dataTable.isDataTable) {
      return $.fn.dataTable.isDataTable(el);
    }
    if ($.fn.dataTable.fnIsDataTable) {
      return $.fn.dataTable.fnIsDataTable(el);
    }
    return false;
  }

  function destroyDbBackupTable() {
    if (!isBackupDtActive()) {
      dbBackupDt = null;
      return;
    }
    if ($.fn.dataTable.isDataTable && $.fn.dataTable.isDataTable($backupTable[0])) {
      $backupTable.DataTable().destroy();
    } else {
      $backupTable.dataTable().fnDestroy();
    }
    dbBackupDt = null;
  }

  if ($.fn.dataTable && $.fn.dataTableExt && !$.fn.dataTableExt.afnSortData.ewDbBackupRunDate) {
    $.fn.dataTableExt.afnSortData.ewDbBackupRunDate = function (oSettings) {
      var out = [];
      $('tbody tr', oSettings.nTable).each(function () {
        out.push($(this).attr('data-date') || '0000-00-00');
      });
      return out;
    };
  }

  function initDbBackupTable() {
    if (!$backupTable.length || !$.fn.dataTable) {
      return;
    }
    if (isBackupDtActive()) {
      return;
    }
    if ($('#ewDbBackupBody tr.ew-db-backup-empty').length) {
      return;
    }

    var legacyOpts = {
      sPaginationType: 'full_numbers',
      iDisplayLength: 10,
      aLengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'All']],
      bSort: true,
      aaSorting: [[0, 'desc']],
      bStateSave: false,
      aoColumnDefs: [
        { sSortDataType: 'ewDbBackupRunDate', aTargets: [0] },
        { bSortable: false, aTargets: [1, 2, 3, 4, 5] },
        { sClass: 'text-center ew-db-backup-date-col col-center', aTargets: [0] },
        { sClass: 'text-center ew-db-backup-fromto-col col-center', aTargets: [3, 4] },
        { sClass: 'text-center col-center', aTargets: [5] }
      ],
      oLanguage: {
        sEmptyTable: 'No backups yet. The daily job runs at 11:30 AM IST.',
        sSearch: ''
      },
      oSearch: { sSearch: '', bSmart: false, bRegex: false, bCaseInsensitive: true }
    };

    dbBackupDt = $backupTable.dataTable(legacyOpts);

    if (typeof applyEwListLayout === 'function') {
      window.setTimeout(applyEwListLayout, 80);
      window.setTimeout(applyEwListLayout, 250);
    }
  }

  function renderRows(rows) {
    if (!rows || !rows.length) {
      $('#ewDbBackupBody').html('<tr class="ew-db-backup-empty"><td colspan="6" class="text-center text-muted">No backups yet. The daily job runs at 11:30 AM IST.</td></tr>');
      $('#ewDbBackupAllBtn').hide();
      return;
    }
    var html = '';
    rows.forEach(function (r) {
      var dot = (r.is_latest || r.is_today) ? '<span class="ew-db-backup-dot ew-db-backup-dot--live" title="Latest backup"></span>' : '';
      html += '<tr data-date="' + r.date + '">'
        + '<td class="ew-db-backup-date-col text-center col-center">'
        + '<span class="ew-db-backup-date-inner"><span class="ew-db-backup-date-text ew-db-backup-date-text--primary">' + $('<div>').text(r.label).html() + '</span>'
        + '<span class="ew-db-backup-dot-slot">' + dot + '</span></span></td>'
        + '<td>' + $('<div>').text(r.description).html() + '</td>'
        + '<td>' + $('<div>').text(r.size_label).html() + '</td>'
        + '<td class="text-center ew-db-backup-fromto-col"><span class="ew-db-backup-date-text">' + $('<div>').text(r.from).html() + '</span></td>'
        + '<td class="text-center ew-db-backup-fromto-col"><span class="ew-db-backup-date-text">' + $('<div>').text(r.to).html() + '</span></td>'
        + '<td class="text-center"><a class="ew-db-backup-dl table-actions" title="Download" href="database_backup_download.php?date=' + encodeURIComponent(r.date) + '"><i class="fa fa-cloud-download"></i></a></td>'
        + '</tr>';
    });
    $('#ewDbBackupBody').html(html);
    if (!$('#ewDbBackupAllBtn').length) {
      $('.ew-toolbar-right').prepend('<a href="database_backup_download.php?all=1" class="ew-btn-v2 ew-btn-v2-outline" id="ewDbBackupAllBtn"><i class="fa fa-cloud-download"></i> Download all</a> ');
    } else {
      $('#ewDbBackupAllBtn').show();
    }
  }

  function refreshList() {
    return $.getJSON('database_backup_data.php', { cmd: 'list' }).then(function (res) {
      if (res && res.status === 0) {
        $('#ewDbBackupCount').text(res.count);
        destroyDbBackupTable();
        renderRows(res.data);
        initDbBackupTable();
      }
    });
  }

  $(document).ready(function () {
    initDbBackupTable();
  });
  $(window).on('load', function () {
    if (!isBackupDtActive()) {
      initDbBackupTable();
    }
  });

})(jQuery);
</script>
<style>
.ew-db-backup-page .ew-db-backup-count {
  margin: 2px 0 0;
  font-size: 13px;
  color: var(--ew-muted, #6B7A8D);
}
.ew-db-backup-card .ew-card-toolbar > div:first-child h2 {
  margin-bottom: 0;
}
.ew-db-backup-table th.ew-db-backup-date-col,
.ew-db-backup-table td.ew-db-backup-date-col,
table.dataTable#ew_db_backup_table thead th.ew-db-backup-date-col,
table.dataTable#ew_db_backup_table tbody td.ew-db-backup-date-col {
  text-align: center !important;
}
table.dataTable#ew_db_backup_table tbody td.text-center {
  text-align: center !important;
}
.ew-db-backup-card #ew_db_backup_table td.ew-db-backup-date-col,
.ew-db-backup-card #ew_db_backup_table th.ew-db-backup-date-col {
  text-align: center !important;
}
.ew-db-backup-table th.ew-db-backup-fromto-col,
.ew-db-backup-table td.ew-db-backup-fromto-col,
table.dataTable#ew_db_backup_table thead th.ew-db-backup-fromto-col,
table.dataTable#ew_db_backup_table tbody td.ew-db-backup-fromto-col {
  text-align: center !important;
}
.ew-db-backup-date-inner {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  margin: 0 auto;
}
.ew-db-backup-date-text {
  display: inline-block;
  text-align: center;
  font-variant-numeric: tabular-nums;
  letter-spacing: 0.01em;
  line-height: 1.35;
}
.ew-db-backup-fromto-col .ew-db-backup-date-text {
  width: 5.85rem;
}
.ew-db-backup-date-text--primary {
  width: auto;
  font-weight: 600;
  color: #06416F;
}
.ew-db-backup-dot-slot {
  width: 8px;
  min-height: 8px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.ew-db-backup-dot {
  display: inline-block;
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #DD111E;
  flex-shrink: 0;
  vertical-align: middle;
}
.ew-db-backup-dot--live {
  animation: ew-db-backup-blink 1.15s ease-in-out infinite;
  box-shadow: 0 0 0 0 rgba(221, 17, 30, 0.55);
}
@keyframes ew-db-backup-blink {
  0%, 100% {
    opacity: 1;
    transform: scale(1);
    box-shadow: 0 0 0 0 rgba(221, 17, 30, 0.55);
  }
  50% {
    opacity: 0.25;
    transform: scale(0.88);
    box-shadow: 0 0 0 5px rgba(221, 17, 30, 0);
  }
}
@media (prefers-reduced-motion: reduce) {
  .ew-db-backup-dot--live {
    animation: none;
  }
}
.ew-db-backup-dl {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 34px;
  height: 34px;
  border-radius: 50%;
  background: #eef1f4;
  color: #06416F;
}
.ew-db-backup-dl:hover {
  background: #dde7f0;
  color: #042C4A;
}
</style>
</body>
</html>
