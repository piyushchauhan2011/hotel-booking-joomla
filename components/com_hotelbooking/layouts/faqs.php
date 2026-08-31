<?php

\defined('_JEXEC') or die;

/** @var array $displayData */

$faqs = $displayData['faqs'] ?? [];

if (empty($faqs)) {
    return;
}
?>
<div class="hb-faqs">
	<?php foreach ($faqs as $faq) : ?>
		<?php if (empty($faq['question'])) : continue; endif; ?>
		<details class="hb-faq-item">
			<summary><?php echo htmlspecialchars($faq['question']); ?></summary>
			<?php if (!empty($faq['answer'])) : ?>
				<div class="hb-faq-answer"><?php echo nl2br(htmlspecialchars($faq['answer'])); ?></div>
			<?php endif; ?>
		</details>
	<?php endforeach; ?>
</div>
