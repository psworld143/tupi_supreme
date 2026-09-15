<?php
$company_name = getSiteSetting('company_short_name', 'Tupi Supreme');
$company_tagline = getSiteSetting('company_tagline', 'Leading provider of premium activated carbon solutions for environmental protection and industrial applications.');
$contact_address = getSiteSetting('contact_address', '123 Industrial Ave, Tupi City');
$contact_phone = getSiteSetting('contact_phone', '+1 (555) 123-4567');
$contact_email = getSiteSetting('contact_email', 'info@tupisupreme.com');
$copyright_year = getSiteSetting('copyright_year', '2024');

$social_media = getSocialMedia();
$quick_links = getFooterLinks('quick_links');
$product_links = getFooterLinks('products');
$legal_links = getFooterLinks('legal');
?>
<!-- Footer -->
<footer class="bg-[#23332c] text-white py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-10">
            <div>
                <h5 class="text-lg font-semibold mb-4 flex items-center">
                    <i class="fas fa-leaf mr-2 text-[#8bc34a]"></i><?php echo htmlspecialchars_safe($company_name); ?>
                </h5>
                <p class="text-white/70 mb-5 text-sm leading-relaxed"><?php echo htmlspecialchars_safe($company_tagline); ?></p>
                <div class="flex space-x-3">
                    <?php foreach ($social_media as $social): ?>
                        <a href="<?php echo htmlspecialchars_safe($social['url']); ?>" class="w-9 h-9 rounded-full bg-white/10 hover:bg-[#3d7a66] text-white flex items-center justify-center transition-colors" target="_blank" rel="noopener">
                            <i class="<?php echo htmlspecialchars_safe($social['icon_class']); ?>"></i>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <div>
                <h6 class="text-sm font-semibold uppercase tracking-wider text-white/60 mb-4">Quick Links</h6>
                <ul class="space-y-2.5">
                    <?php foreach ($quick_links as $link): ?>
                        <li><a href="<?php echo htmlspecialchars_safe($link['url']); ?>" class="text-white/75 hover:text-[#8bc34a] transition-colors text-sm"><?php echo htmlspecialchars_safe($link['label']); ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div>
                <h6 class="text-sm font-semibold uppercase tracking-wider text-white/60 mb-4">Products</h6>
                <ul class="space-y-2.5">
                    <?php foreach ($product_links as $link): ?>
                        <li><a href="<?php echo htmlspecialchars_safe($link['url']); ?>" class="text-white/75 hover:text-[#8bc34a] transition-colors text-sm"><?php echo htmlspecialchars_safe($link['label']); ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div>
                <h6 class="text-sm font-semibold uppercase tracking-wider text-white/60 mb-4">Contact Info</h6>
                <ul class="space-y-3 text-white/75 text-sm">
                    <li class="flex items-start">
                        <i class="fas fa-map-marker-alt mr-3 mt-1 text-[#3d7a66]"></i><span><?php echo htmlspecialchars_safe($contact_address); ?></span>
                    </li>
                    <li class="flex items-center">
                        <i class="fas fa-phone mr-3 text-[#3d7a66]"></i><?php echo htmlspecialchars_safe($contact_phone); ?>
                    </li>
                    <li class="flex items-center">
                        <i class="fas fa-envelope mr-3 text-[#3d7a66]"></i><?php echo htmlspecialchars_safe($contact_email); ?>
                    </li>
                </ul>
            </div>
        </div>
        <hr class="border-white/10 my-8">
        <div class="flex flex-col md:flex-row justify-between items-center">
            <p class="text-white/60 text-sm">&copy; <?php echo htmlspecialchars_safe($copyright_year); ?> <?php echo htmlspecialchars_safe(getSiteSetting('company_name', 'Tupi Supreme Activated Carbon, Inc.')); ?>. All rights reserved.</p>
            <div class="flex space-x-5 mt-4 md:mt-0">
                <?php foreach ($legal_links as $link): ?>
                    <a href="<?php echo htmlspecialchars_safe($link['url']); ?>" class="text-white/60 hover:text-[#8bc34a] transition-colors text-sm"><?php echo htmlspecialchars_safe($link['label']); ?></a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</footer>
