<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class SeedBadgeTags extends BaseMigration
{
    /**
     * @return void
     */
    public function up(): void
    {
        $this->execute(<<<'SQL'
            INSERT INTO badge_tags (id, tag_name, tag_search_text, tag_category, tag_order)
            VALUES
                ('a2f81bc1-9f38-4c58-a5e4-f57fbd331a15', 'Squirrels', '^Squirrel', 10, 10),
                ('0df9c8f5-6d66-4be7-b8b4-67585a7a374c', 'Beavers', '^Beaver', 10, 20),
                ('b22e1399-866c-4db1-bb1b-bf01f33eaf59', 'Cubs', '^Cub', 10, 30),
                ('3ffca914-64da-4c78-aea1-5ab6dc3c712a', 'Scouts', '^Scout', 10, 40),
                ('8b38ee61-7018-4b45-98be-48b4bd33afe3', 'Explorers', '^Explorer', 10, 50),
                ('7d4f5582-6db9-41a0-bc8e-26a8bd46eb2a', 'Challenge', 'Challenge Award Badge', 20, 120),
                ('17e2368b-68d4-4b27-ac1f-20cc0216ccd6', 'Activity', 'Activity Badge', 20, 140),
                ('88dd355c-e760-453f-b97d-040d42878ee6', 'Patrol', 'Patrol', 20, 160),
                ('9acd0a04-9db1-4161-a2f7-af2a1f405d79', 'Staged', 'Stage', 20, 180)
            ON CONFLICT (id) DO UPDATE SET
                tag_name = EXCLUDED.tag_name,
                tag_search_text = EXCLUDED.tag_search_text,
                tag_category = EXCLUDED.tag_category,
                tag_order = EXCLUDED.tag_order
            SQL);
    }

    /**
     * @return void
     */
    public function down(): void
    {
        $this->execute(<<<'SQL'
            DELETE FROM badge_tags
            WHERE id IN (
                'a2f81bc1-9f38-4c58-a5e4-f57fbd331a15',
                '0df9c8f5-6d66-4be7-b8b4-67585a7a374c',
                'b22e1399-866c-4db1-bb1b-bf01f33eaf59',
                '3ffca914-64da-4c78-aea1-5ab6dc3c712a',
                '8b38ee61-7018-4b45-98be-48b4bd33afe3',
                '7d4f5582-6db9-41a0-bc8e-26a8bd46eb2a',
                '17e2368b-68d4-4b27-ac1f-20cc0216ccd6',
                '88dd355c-e760-453f-b97d-040d42878ee6',
                '9acd0a04-9db1-4161-a2f7-af2a1f405d79'
            )
            SQL);
    }
}
