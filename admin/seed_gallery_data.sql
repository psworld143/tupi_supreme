-- Gallery Images Sample Data
-- This file contains sample gallery images for all categories
-- Run this to populate the gallery_images table with sample data

USE tsaci_cms;

-- Clear existing data (optional - uncomment if you want to reset)
-- DELETE FROM gallery_images;

-- Insert Gallery Images
INSERT INTO gallery_images (title, description, image_url, category, display_order, is_active) VALUES
-- Facilities Category
('Production Facility', 'State-of-the-art manufacturing plant with advanced equipment for activated carbon production', 'https://via.placeholder.com/800x600/2c5530/ffffff?text=Production+Facility', 'facilities', 1, 1),
('Storage Warehouse', 'Quality controlled storage facilities ensuring optimal conditions for activated carbon products', 'https://via.placeholder.com/800x600/4a7c59/ffffff?text=Storage+Warehouse', 'facilities', 2, 1),
('Quality Laboratory', 'ISO-certified testing facility for rigorous quality control and product analysis', 'https://via.placeholder.com/800x600/8bc34a/ffffff?text=Quality+Laboratory', 'facilities', 3, 1),
('Main Office Building', 'Corporate headquarters housing our administrative and technical teams', 'https://via.placeholder.com/800x600/2c5530/ffffff?text=Main+Office', 'facilities', 4, 1),

-- Products Category
('Granulated Activated Carbon', 'Premium 2mm granulated activated carbon meeting strict quality specifications', 'https://via.placeholder.com/800x600/4a7c59/ffffff?text=Granulated+GAC', 'products', 1, 1),
('Coconut Husk Products', 'High-quality coconut husk growing medium products for agricultural applications', 'https://via.placeholder.com/800x600/8bc34a/ffffff?text=Coconut+Husk', 'products', 2, 1),
('Product Packaging', 'Quality packaging solutions ensuring product integrity during transport', 'https://via.placeholder.com/800x600/2c5530/ffffff?text=Product+Packaging', 'products', 3, 1),
('Bulk Product Storage', 'Large-scale storage of activated carbon products ready for distribution', 'https://via.placeholder.com/800x600/4a7c59/ffffff?text=Bulk+Storage', 'products', 4, 1),

-- Manufacturing Process Category
('Activation Process', 'Steam activation technology creating high-surface-area activated carbon', 'https://via.placeholder.com/800x600/8bc34a/ffffff?text=Activation+Process', 'process', 1, 1),
('Carbonization', 'High-temperature processing converting raw materials into activated carbon', 'https://via.placeholder.com/800x600/2c5530/ffffff?text=Carbonization', 'process', 2, 1),
('Quality Control', 'Rigorous testing procedures ensuring consistent product quality and specifications', 'https://via.placeholder.com/800x600/4a7c59/ffffff?text=Quality+Control', 'process', 3, 1),
('Screening & Grading', 'Precise particle size screening to meet exact customer specifications', 'https://via.placeholder.com/800x600/8bc34a/ffffff?text=Screening+Grading', 'process', 4, 1),

-- Installations Category
('Municipal Water Treatment', 'Installation at municipal water treatment facility showcasing our activated carbon solutions', 'https://via.placeholder.com/800x600/2c5530/ffffff?text=Municipal+Installation', 'installations', 1, 1),
('Treatment Plant', 'Large-scale municipal facility installation with integrated filtration systems', 'https://via.placeholder.com/800x600/4a7c59/ffffff?text=Treatment+Plant', 'installations', 2, 1),
('Installation Process', 'Professional installation team ensuring proper setup and system integration', 'https://via.placeholder.com/800x600/8bc34a/ffffff?text=Installation+Team', 'installations', 3, 1),
('Filtration System', 'Complete activated carbon filtration system in operation at customer facility', 'https://via.placeholder.com/800x600/2c5530/ffffff?text=Filtration+System', 'installations', 4, 1),

-- Team & Operations Category
('Expert Team', 'Dedicated professionals committed to excellence in activated carbon solutions', 'https://via.placeholder.com/800x600/4a7c59/ffffff?text=Expert+Team', 'team', 1, 1),
('Technical Experts', 'Quality assurance team ensuring product specifications and customer satisfaction', 'https://via.placeholder.com/800x600/8bc34a/ffffff?text=Technical+Experts', 'team', 2, 1),
('Customer Service', 'Client consultation and support team providing personalized service', 'https://via.placeholder.com/800x600/2c5530/ffffff?text=Customer+Service', 'team', 3, 1),
('Awards & Recognition', 'Industry achievements and certifications recognizing our commitment to quality', 'https://via.placeholder.com/800x600/4a7c59/ffffff?text=Awards+Recognition', 'team', 4, 1),
('Training Session', 'Employee training program ensuring continuous improvement and skill development', 'https://via.placeholder.com/800x600/8bc34a/ffffff?text=Training+Session', 'team', 5, 1)
ON DUPLICATE KEY UPDATE 
    title=VALUES(title), 
    description=VALUES(description), 
    image_url=VALUES(image_url),
    category=VALUES(category),
    display_order=VALUES(display_order);

-- Verify data
SELECT 'Gallery Images Seeded Successfully!' as status;
SELECT category, COUNT(*) as count FROM gallery_images GROUP BY category ORDER BY category;

