
# Senior Core PHP Developer Prompt

You are a Senior Core PHP Developer with expertise in PHP 7.x, MySQL, jQuery, Bootstrap, AJAX, and Logistics Management Systems. Your task is to enhance my existing Core PHP project without breaking any existing functionality. Reuse the current project structure, database, and coding style. Do not rewrite the application from scratch.

---

## Project Overview

This is a Logistics Management System (EliteWave360) where:

- Admin creates Transactions (GRN).
- Drivers are assigned consignments.
- Drivers update shipment status.
- Admin manages transactions through `transaction_list.php`.
- There is already a POD module consisting of:
  - `pod_master.php`
  - `pod_list.php`
  - `pod_files` table

Currently, the POD module is generic and is **not linked to any specific GRN**. I need to convert it into a **GRN-wise Proof of Delivery (POD) Management System**.

---

# Existing Functionality

### Admin Side

- Transaction List (`transaction_list.php`)
- Action column already contains:
  - Edit
  - Delete
  - Print
  - Dropdown Menu
    - Consignor GR
    - Consignee GR
    - POD GR
    - Accounts GR

Do **NOT** modify or remove any of these existing features.

---

### Driver Side

Driver Dashboard currently displays:

- Assigned GRNs
- Status
- Update Status button
- POD button

Currently, the POD upload is generic and not related to the selected GRN.

---

# Required Changes

## 1. GRN-wise POD Upload

Every uploaded POD must belong to a single GRN.

Instead of uploading a generic POD, the upload page must automatically know which GRN is being uploaded.

Example:

```
Driver Dashboard

GRN : SHAB00002

Update Status

Upload POD
```

When the driver clicks Upload POD:

```
pod_master.php?grn=SHAB00002
```

The driver should NOT manually select the GRN.

The GRN must be passed automatically.

---

## 2. Driver Dashboard Logic

The Upload POD button should only appear when:

```
Transaction Status = Completed
```

or

```
Consignment Delivered Successfully
```

If the shipment is:

- Pending
- Booked
- Out for Delivery
- In Transit

Then:

- Hide Upload POD button

OR

- Disable it.

---

## 3. Upload Screen

Instead of the current generic upload page, display:

```
Upload Proof of Delivery

GRN Number:
SHAB00002

Choose File

Upload
```

The GRN Number should be read-only.

---

## 4. Database Changes

Modify the existing `pod_files` table.

Add the following column:

```
grn_number VARCHAR(...)
```

Example structure:

```
id
grn_number
driver_id
file_name
created_at
created_by
updated_at
status
```

Every uploaded POD must store its corresponding GRN Number.

---

## 5. Duplicate Upload Handling

Each GRN should have only one active POD.

If the driver uploads again for the same GRN:

- Replace the existing file

OR

- Update the existing record

Do NOT create duplicate records for the same GRN.

---

## 6. Driver Restrictions

The driver should only upload POD for:

- Assigned GRNs
- Completed Deliveries

The driver must NOT upload POD for:

- Other driver's consignments
- Pending consignments
- Cancelled consignments

Validate this in PHP before uploading.

---

# Admin Side Changes

## Transaction List

Inside:

```
transaction_list.php
```

The Action column already contains:

- Edit
- Delete
- Print
- Dropdown Menu

Keep everything exactly as it is.

Add one new Action icon.

Example:

```
Edit

Delete

Print

POD Image
```

This icon represents the Driver Uploaded POD.

---

## Display Logic

For every transaction:

If POD exists for that GRN

Show:

```
View POD
```

If POD does not exist

Show:

- Disabled icon

OR

- "No POD"

---

## View POD

When the admin clicks the new POD icon:

Open the uploaded POD belonging to that GRN.

Support:

- JPG
- JPEG
- PNG
- PDF

If multiple files are allowed in future, display all files linked to that GRN.

---

# POD List Page

Current:

```
pod_list.php
```

Currently displays:

- Date
- Image Count
- Filename

for all uploaded files.

Change it to display GRN-wise information.

Example:

| GRN Number | Upload Date | Driver | Image Count | View | Edit | Delete |
|------------|-------------|---------|-------------|------|------|--------|

Every row should represent one GRN instead of a generic upload.

---

# Driver Dashboard Flow

```
Driver Login

↓

Dashboard

↓

Assigned Transactions

↓

Update Status

↓

Status becomes Completed

↓

Upload POD button appears

↓

Driver uploads POD

↓

POD stored against that GRN

↓

Admin Transaction List

↓

POD icon automatically appears for that GRN

↓

Admin clicks icon

↓

Uploaded POD opens
```

---

# Validation Requirements

Validate the following before upload:

- GRN exists.
- Driver is assigned to that GRN.
- Status is Completed.
- File type:
  - JPG
  - JPEG
  - PNG
  - PDF
- Maximum upload size validation.
- Prevent SQL Injection.
- Prevent duplicate uploads for the same GRN.
- Replace the existing POD if uploading again.

---

# Existing Features Must Remain Untouched

Do NOT modify or break:

- Transaction creation
- GRN generation
- Status update logic
- Existing POD GR generation
- Consignor GR
- Consignee GR
- Accounts GR
- Existing transaction workflow

Only extend the current functionality by linking uploaded POD files to their corresponding GRN.

---

# Coding Standards

- Use existing project coding style.
- Use Core PHP (PHP 7.x compatible).
- Use MySQLi.
- Reuse existing functions whenever possible.
- Avoid duplicate code.
- Keep the implementation modular and maintainable.
- Preserve UI consistency with the existing application.
- Add proper comments wherever new logic is introduced.

---

# Expected Result

After implementation:

- Drivers can upload POD only after delivery is completed.
- Every POD is linked to its corresponding GRN.
- Admin can directly view the uploaded POD from the respective transaction row.
- `pod_list.php` becomes GRN-wise instead of a generic upload list.
- The entire implementation integrates seamlessly with the existing Core PHP Logistics Management System without affecting current functionality.