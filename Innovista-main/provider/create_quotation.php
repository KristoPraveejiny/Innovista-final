
<?php
require_once '../config/session.php';
protectPage('provider');
require_once '../config/Database.php';
require_once '../provider/provider_header.php'; 

$quotation_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$quotation_id) {
	echo '<div style="color:red;">Invalid quotation ID.</div>';
	exit();
}
$db = (new Database())->getConnection();
$stmt = $db->prepare('SELECT q.*, u.name as customer_name FROM quotations q JOIN users u ON q.customer_id = u.id WHERE q.id = :id');
$stmt->bindParam(':id', $quotation_id);
$stmt->execute();
$quote = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$quote) {
	echo '<div style="color:red;">Quotation not found.</div>';
	exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Create & Send Quotation</title>
	<link rel="stylesheet" href="../public/assets/css/create_quotation.css">
</head>
<body>
<div class="quotation-container card-pro">
	<h2 class="quotation-title-pro">Create &amp; Send Quotation</h2>
	<div class="quotation-details card-section-pro">
		<div class="quotation-row-pro">
			<div class="quotation-label-pro">Customer Name:</div>
			<div class="quotation-value-pro"><?php echo htmlspecialchars($quote['customer_name']); ?></div>
		</div>
		<div class="quotation-row-pro">
			<div class="quotation-label-pro">Service:</div>
			<div class="quotation-value-pro"><?php
				$service = $quote['service_type'];
				$subcategory = isset($quote['subcategory']) ? $quote['subcategory'] : '';
				echo htmlspecialchars($service);
				if ($subcategory) {
					echo ' — <span class="service-subcat-pro">' . htmlspecialchars($subcategory) . '</span>';
				}
			?></div>
		</div>
		<div class="quotation-row-pro">
			<div class="quotation-label-pro">Description:</div>
			<div class="quotation-value-pro"><?php echo htmlspecialchars($quote['project_description']); ?></div>
		</div>
		<?php if (!empty($quote['photos'])): ?>
		<div class="quotation-row-pro">
			<div class="quotation-label-pro">Images:</div>
			<div class="quotation-value-pro">
				<div class="quotation-images-pro">
				<?php foreach (explode(',', $quote['photos']) as $img): ?>
					<img src="../<?php echo htmlspecialchars($img); ?>" class="quotation-thumb-pro" onclick="showBigImage(this.src)">
				<?php endforeach; ?>
				</div>
				<!-- Modal for big image -->
				<div id="bigImageModal" class="big-image-modal-pro">
					<span onclick="closeBigImage()" class="big-image-close-pro">&times;</span>
					<img id="bigImage" src="" class="big-image-pro">
				</div>
			</div>
		</div>
		<?php endif; ?>
	</div>
	<form class="styled-form quotation-form-pro" method="POST" action="save_quotation.php" enctype="multipart/form-data">
		<input type="hidden" name="quotation_id" value="<?php echo $quotation_id; ?>">
		<div class="form-row-pro">
			<div class="form-group-pro">
				<label for="amount">Quotation Amount (Rs)</label>
				<input type="number" name="amount" id="amount" required min="0" step="0.01" placeholder="Enter total amount">
			</div>
			<div class="form-group-pro">
				<label for="advance">Advance (25%)</label>
				<input type="number" name="advance" id="advance" readonly placeholder="Auto-calculated">
			</div>
			<div class="form-group-pro">
				<label for="extra_amount">Extra Amount (Rs)</label>
				<input type="number" name="extra_amount" id="extra_amount" min="0" step="0.01" placeholder="Auto-calculated" readonly>
			</div>
		</div>
		<div class="timeline-row-pro">
			<div class="timeline-group-pro">
				<label for="start_date">Start Date</label>
				<input type="date" name="start_date" id="start_date" required>
			</div>
			<div class="timeline-group-pro">
				<label for="end_date">Estimated End Date</label>
				<input type="date" name="end_date" id="end_date" required>
			</div>
		</div>
		<div class="form-group-pro">
			<label for="validity">Validity Period For Project</label>
			<input type="number" name="validity" id="validity" required min="1" placeholder="e.g. 30">
		</div>
		<div class="form-group-pro">
			<label for="provider_notes">Provider Extra Notes</label>
			<textarea name="provider_notes" id="provider_notes" rows="3" placeholder="Add any extra notes or details..."></textarea>
		</div>
		<button type="submit" class="btn-submit-pro">Send Quotation</button>
	</form>
</div>
<script>
// Quotation calculation logic
(function(){
	var amountEl = document.getElementById('amount');
	var advanceEl = document.getElementById('advance');
	var extraEl = document.getElementById('extra_amount');

	function toMoney(n){
		return (Math.round((n + Number.EPSILON) * 100) / 100).toFixed(2);
	}

	function recalc(){
		var amt = parseFloat(amountEl.value);
		if (isNaN(amt) || amt < 0) { amt = 0; }
		var adv = amt * 0.25; // 25% advance
		advanceEl.value = toMoney(adv);
		var extra = amt - adv;
		if (extra < 0) extra = 0;
		extraEl.value = toMoney(extra);
	}

	amountEl.addEventListener('input', recalc);
	document.addEventListener('DOMContentLoaded', recalc);
})();

// Image modal logic
function showBigImage(src) {
	var modal = document.getElementById('bigImageModal');
	var img = document.getElementById('bigImage');
	img.src = src;
	modal.style.display = 'flex';
	// Add event listener for outside click
	modal.onclick = function(e) {
		if (e.target === modal) closeBigImage();
	};
}
function closeBigImage() {
	var modal = document.getElementById('bigImageModal');
	modal.style.display = 'none';
	document.getElementById('bigImage').src = '';
	modal.onclick = null;
}
</script>

</body>
</html>
