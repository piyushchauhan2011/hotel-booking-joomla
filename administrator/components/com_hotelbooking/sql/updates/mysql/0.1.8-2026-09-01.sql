-- Minimal bilingual demo content to verify the Thai (th-TH) multilingual setup end-to-end:
-- one destination, one room, and one FAQ, each with a linked en-GB/th-TH pair.
-- Uses a reserved id range (9001+) to avoid colliding with real content.

INSERT INTO `#__hotelbooking_destinations`
    (`id`, `name`, `alias`, `language`, `description`, `image`, `commission_rate`, `published`, `ordering`)
VALUES
    (9001, 'Chiang Mai Riverside Retreat', 'chiang-mai-riverside-retreat', 'en-GB',
        '<p>A tranquil riverside retreat in the heart of Chiang Mai, surrounded by lush gardens and traditional Lanna architecture.</p>',
        '', 8.00, 1, 1),
    (9002, 'รีสอร์ทริมน้ำเชียงใหม่', 'chiang-mai-riverside-retreat', 'th-TH',
        '<p>รีสอร์ทริมน้ำอันเงียบสงบใจกลางเชียงใหม่ รายล้อมด้วยสวนเขียวขจีและสถาปัตยกรรมล้านนาแบบดั้งเดิม</p>',
        '', 8.00, 1, 1);

INSERT INTO `#__hotelbooking_rooms`
    (`id`, `destination_id`, `name`, `alias`, `language`, `description`, `price`, `capacity`, `image`, `published`, `ordering`)
VALUES
    (9101, 9001, 'Deluxe Garden Room', 'deluxe-garden-room', 'en-GB',
        '<p>A spacious room overlooking the garden, with a private balcony and a king-size bed.</p>',
        1800.00, 2, '', 1, 1),
    (9102, 9002, 'ห้องดีลักซ์วิวสวน', 'deluxe-garden-room', 'th-TH',
        '<p>ห้องพักกว้างขวางวิวสวน พร้อมระเบียงส่วนตัวและเตียงคิงไซส์</p>',
        1800.00, 2, '', 1, 1);

INSERT INTO `#__hotelbooking_faqs`
    (`id`, `question`, `answer`, `scope`, `language`, `published`, `ordering`)
VALUES
    (9201, 'Is breakfast included in the room rate?',
        '<p>Yes, a complimentary breakfast is included with every booking.</p>',
        'general', 'en-GB', 1, 1),
    (9202, 'ราคาห้องพักรวมอาหารเช้าหรือไม่?',
        '<p>ใช่ อาหารเช้าฟรีรวมอยู่ในการจองทุกครั้ง</p>',
        'general', 'th-TH', 1, 1);

INSERT INTO `#__associations` (`id`, `context`, `key`)
VALUES
    (9001, 'com_hotelbooking.item.destination', MD5('hb-demo-destination-9001-9002')),
    (9002, 'com_hotelbooking.item.destination', MD5('hb-demo-destination-9001-9002')),
    (9101, 'com_hotelbooking.item.room', MD5('hb-demo-room-9101-9102')),
    (9102, 'com_hotelbooking.item.room', MD5('hb-demo-room-9101-9102')),
    (9201, 'com_hotelbooking.item.faq', MD5('hb-demo-faq-9201-9202')),
    (9202, 'com_hotelbooking.item.faq', MD5('hb-demo-faq-9201-9202'));
