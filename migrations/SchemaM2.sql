CREATE TABLE revolving_doors (id INT AUTO_INCREMENT NOT NULL, factual_note_fr LONGTEXT DEFAULT NULL, factual_note_en LONGTEXT DEFAULT NULL, delay_days INT DEFAULT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, person_id INT NOT NULL, source_position_id INT NOT NULL, target_position_id INT NOT NULL, linking_action_id INT DEFAULT NULL, created_by_id INT DEFAULT NULL, INDEX IDX_8E28E496217BBB47 (person_id), INDEX IDX_8E28E496E1FB7EE6 (source_position_id), INDEX IDX_8E28E4965ABE614C (target_position_id), INDEX IDX_8E28E496A54E71AC (linking_action_id), INDEX IDX_8E28E496B03A8386 (created_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`;

CREATE TABLE legislative_actions (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(30) NOT NULL, action_date DATE NOT NULL, title_fr VARCHAR(300) NOT NULL, title_en VARCHAR(300) DEFAULT NULL, description_fr LONGTEXT NOT NULL, description_en LONGTEXT DEFAULT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, author_id INT NOT NULL, contextual_position_id INT DEFAULT NULL, created_by_id INT DEFAULT NULL, INDEX IDX_7209036CF675F31B (author_id), INDEX IDX_7209036C9988B9C3 (contextual_position_id), INDEX IDX_7209036CB03A8386 (created_by_id), INDEX idx_legact_date (action_date), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`;

CREATE TABLE legislative_action_beneficiary (legislative_action_id INT NOT NULL, organization_id INT NOT NULL, INDEX IDX_94AB4D59DE2FDFCC (legislative_action_id), INDEX IDX_94AB4D5932C8A3DE (organization_id), PRIMARY KEY (legislative_action_id, organization_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`;

CREATE TABLE change_proposals (id INT AUTO_INCREMENT NOT NULL, entity_type VARCHAR(50) NOT NULL, entity_id INT NOT NULL, diff JSON NOT NULL, justification LONGTEXT NOT NULL, status VARCHAR(20) NOT NULL, moderated_at DATETIME DEFAULT NULL, rejection_reason LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, submitted_by_id INT NOT NULL, moderated_by_id INT DEFAULT NULL, INDEX IDX_E2A70FEC79F7D87D (submitted_by_id), INDEX IDX_E2A70FEC8EDA19B0 (moderated_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`;

CREATE TABLE person_similarities (id INT AUTO_INCREMENT NOT NULL, score NUMERIC(8, 2) NOT NULL, details JSON NOT NULL, computed_at DATETIME NOT NULL, person_a_id INT NOT NULL, person_b_id INT NOT NULL, INDEX IDX_3EDF56F7B138D773 (person_a_id), INDEX IDX_3EDF56F7A38D789D (person_b_id), INDEX idx_similarity_a_score (person_a_id, score), UNIQUE INDEX uniq_similarity_pair (person_a_id, person_b_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`;

CREATE TABLE entity_sources (id INT AUTO_INCREMENT NOT NULL, entity_type VARCHAR(30) NOT NULL, entity_id INT NOT NULL, created_at DATETIME NOT NULL, source_id INT NOT NULL, added_by_id INT DEFAULT NULL, INDEX IDX_419057D1953C1C61 (source_id), INDEX IDX_419057D155B127A4 (added_by_id), INDEX idx_entity_source_target (entity_type, entity_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`;

CREATE TABLE sources (id INT AUTO_INCREMENT NOT NULL, url VARCHAR(1000) NOT NULL, title VARCHAR(500) DEFAULT NULL, type VARCHAR(30) NOT NULL, domain VARCHAR(200) NOT NULL, accessed_at DATE NOT NULL, check_status VARCHAR(20) NOT NULL, last_checked_at DATETIME DEFAULT NULL, wayback_url VARCHAR(500) DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`;

CREATE TABLE right_of_reply_requests (id INT AUTO_INCREMENT NOT NULL, requester_name VARCHAR(200) NOT NULL, requester_quality VARCHAR(200) NOT NULL, requester_email VARCHAR(180) NOT NULL, requester_phone VARCHAR(40) DEFAULT NULL, identity_pdf_path VARCHAR(500) NOT NULL, request_type VARCHAR(30) NOT NULL, body LONGTEXT NOT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, person_id INT NOT NULL, INDEX IDX_E4D86EE3217BBB47 (person_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`;

CREATE TABLE reports (id INT AUTO_INCREMENT NOT NULL, entity_type VARCHAR(50) NOT NULL, entity_id INT NOT NULL, reason VARCHAR(30) NOT NULL, description LONGTEXT NOT NULL, contact_email VARCHAR(180) DEFAULT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, processed_at DATETIME DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`;

CREATE TABLE countries (iso_code VARCHAR(2) NOT NULL, name_fr VARCHAR(100) NOT NULL, name_en VARCHAR(100) NOT NULL, continent VARCHAR(30) NOT NULL, PRIMARY KEY (iso_code)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`;

CREATE TABLE users (id INT AUTO_INCREMENT NOT NULL, uuid BINARY(16) NOT NULL, email VARCHAR(180) NOT NULL, username VARCHAR(50) NOT NULL, password VARCHAR(255) NOT NULL, roles JSON NOT NULL, status VARCHAR(20) NOT NULL, cgu_accepted_at DATETIME NOT NULL, email_verified_at DATETIME DEFAULT NULL, email_verification_token VARCHAR(64) DEFAULT NULL, email_verification_token_expires_at DATETIME DEFAULT NULL, last_login_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_1483A5E9D17F50A6 (uuid), UNIQUE INDEX UNIQ_1483A5E9E7927C74 (email), UNIQUE INDEX UNIQ_1483A5E9F85E0677 (username), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`;

CREATE TABLE parties (id INT AUTO_INCREMENT NOT NULL, european_family VARCHAR(30) DEFAULT NULL, international_family VARCHAR(100) DEFAULT NULL, color_hex VARCHAR(7) DEFAULT NULL, organization_id INT NOT NULL, UNIQUE INDEX UNIQ_4363180532C8A3DE (organization_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`;

CREATE TABLE organizations (id INT AUTO_INCREMENT NOT NULL, uuid BINARY(16) NOT NULL, slug VARCHAR(250) NOT NULL, official_name VARCHAR(250) NOT NULL, type VARCHAR(30) NOT NULL, website_url VARCHAR(500) DEFAULT NULL, founded_year SMALLINT DEFAULT NULL, dissolved_year SMALLINT DEFAULT NULL, wikidata_id VARCHAR(50) DEFAULT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_427C1C7FD17F50A6 (uuid), UNIQUE INDEX UNIQ_427C1C7F989D9B62 (slug), UNIQUE INDEX UNIQ_427C1C7F2A67038D (wikidata_id), INDEX idx_org_type (type), INDEX idx_org_wikidata (wikidata_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`;

CREATE TABLE organization_country (organization_id INT NOT NULL, country_iso_code VARCHAR(2) NOT NULL, INDEX IDX_9EA1412532C8A3DE (organization_id), INDEX IDX_9EA14125517CF5E3 (country_iso_code), PRIMARY KEY (organization_id, country_iso_code)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`;

CREATE TABLE organization_translations (id INT AUTO_INCREMENT NOT NULL, locale VARCHAR(5) NOT NULL, name VARCHAR(500) NOT NULL, description LONGTEXT DEFAULT NULL, organization_id INT NOT NULL, INDEX IDX_1502E9732C8A3DE (organization_id), UNIQUE INDEX uniq_org_locale (organization_id, locale), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`;

CREATE TABLE persons (id INT AUTO_INCREMENT NOT NULL, uuid BINARY(16) NOT NULL, slug VARCHAR(200) NOT NULL, given_name VARCHAR(100) NOT NULL, family_name VARCHAR(100) NOT NULL, usage_name VARCHAR(100) DEFAULT NULL, birth_date DATE DEFAULT NULL, death_date DATE DEFAULT NULL, gender VARCHAR(20) DEFAULT NULL, role_categories LONGTEXT NOT NULL, photo_url VARCHAR(500) DEFAULT NULL, wikidata_id VARCHAR(50) DEFAULT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, deleted_at DATETIME DEFAULT NULL, created_by_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_A25CC7D3D17F50A6 (uuid), UNIQUE INDEX UNIQ_A25CC7D3989D9B62 (slug), UNIQUE INDEX UNIQ_A25CC7D32A67038D (wikidata_id), INDEX IDX_A25CC7D3B03A8386 (created_by_id), INDEX idx_person_status (status), INDEX idx_person_wikidata (wikidata_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`;

CREATE TABLE person_nationality (person_id INT NOT NULL, country_iso_code VARCHAR(2) NOT NULL, INDEX IDX_E72ED4F8217BBB47 (person_id), INDEX IDX_E72ED4F8517CF5E3 (country_iso_code), PRIMARY KEY (person_id, country_iso_code)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`;

CREATE TABLE person_translations (id INT AUTO_INCREMENT NOT NULL, locale VARCHAR(5) NOT NULL, description LONGTEXT DEFAULT NULL, biography_summary LONGTEXT DEFAULT NULL, person_id INT NOT NULL, INDEX IDX_25B93ACA217BBB47 (person_id), UNIQUE INDEX uniq_person_locale (person_id, locale), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`;

CREATE TABLE positions (id INT AUTO_INCREMENT NOT NULL, title_fr VARCHAR(200) NOT NULL, title_en VARCHAR(200) DEFAULT NULL, nature VARCHAR(30) NOT NULL, start_date DATE NOT NULL, end_date DATE DEFAULT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, person_id INT NOT NULL, organization_id INT NOT NULL, country_iso_code VARCHAR(2) DEFAULT NULL, created_by_id INT DEFAULT NULL, INDEX IDX_D69FE57C217BBB47 (person_id), INDEX IDX_D69FE57C32C8A3DE (organization_id), INDEX IDX_D69FE57C517CF5E3 (country_iso_code), INDEX IDX_D69FE57CB03A8386 (created_by_id), INDEX idx_position_nature (nature), INDEX idx_position_startdate (start_date), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`;

CREATE TABLE memberships (id INT AUTO_INCREMENT NOT NULL, year SMALLINT DEFAULT NULL, start_date DATE DEFAULT NULL, end_date DATE DEFAULT NULL, role_in_organization VARCHAR(100) DEFAULT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, person_id INT NOT NULL, organization_id INT NOT NULL, created_by_id INT DEFAULT NULL, INDEX IDX_865A4776217BBB47 (person_id), INDEX IDX_865A477632C8A3DE (organization_id), INDEX IDX_865A4776B03A8386 (created_by_id), INDEX idx_membership_pair (person_id, organization_id), INDEX idx_membership_year (year), INDEX idx_membership_start (start_date), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`;

CREATE TABLE revisions (id INT AUTO_INCREMENT NOT NULL, entity_type VARCHAR(50) NOT NULL, entity_id INT NOT NULL, field_changed VARCHAR(100) NOT NULL, old_value JSON DEFAULT NULL, new_value JSON DEFAULT NULL, created_at DATETIME NOT NULL, proposed_by_id INT NOT NULL, validated_by_id INT NOT NULL, INDEX IDX_89B12285DAB5A938 (proposed_by_id), INDEX IDX_89B12285C69DE5E5 (validated_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`;

ALTER TABLE revolving_doors ADD CONSTRAINT FK_8E28E496217BBB47 FOREIGN KEY (person_id) REFERENCES persons (id) ON DELETE CASCADE;

ALTER TABLE revolving_doors ADD CONSTRAINT FK_8E28E496E1FB7EE6 FOREIGN KEY (source_position_id) REFERENCES positions (id) ON DELETE CASCADE;

ALTER TABLE revolving_doors ADD CONSTRAINT FK_8E28E4965ABE614C FOREIGN KEY (target_position_id) REFERENCES positions (id) ON DELETE CASCADE;

ALTER TABLE revolving_doors ADD CONSTRAINT FK_8E28E496A54E71AC FOREIGN KEY (linking_action_id) REFERENCES legislative_actions (id);

ALTER TABLE revolving_doors ADD CONSTRAINT FK_8E28E496B03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id);

ALTER TABLE legislative_actions ADD CONSTRAINT FK_7209036CF675F31B FOREIGN KEY (author_id) REFERENCES persons (id) ON DELETE CASCADE;

ALTER TABLE legislative_actions ADD CONSTRAINT FK_7209036C9988B9C3 FOREIGN KEY (contextual_position_id) REFERENCES positions (id);

ALTER TABLE legislative_actions ADD CONSTRAINT FK_7209036CB03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id);

ALTER TABLE legislative_action_beneficiary ADD CONSTRAINT FK_94AB4D59DE2FDFCC FOREIGN KEY (legislative_action_id) REFERENCES legislative_actions (id) ON DELETE CASCADE;

ALTER TABLE legislative_action_beneficiary ADD CONSTRAINT FK_94AB4D5932C8A3DE FOREIGN KEY (organization_id) REFERENCES organizations (id) ON DELETE CASCADE;

ALTER TABLE change_proposals ADD CONSTRAINT FK_E2A70FEC79F7D87D FOREIGN KEY (submitted_by_id) REFERENCES users (id);

ALTER TABLE change_proposals ADD CONSTRAINT FK_E2A70FEC8EDA19B0 FOREIGN KEY (moderated_by_id) REFERENCES users (id);

ALTER TABLE person_similarities ADD CONSTRAINT FK_3EDF56F7B138D773 FOREIGN KEY (person_a_id) REFERENCES persons (id) ON DELETE CASCADE;

ALTER TABLE person_similarities ADD CONSTRAINT FK_3EDF56F7A38D789D FOREIGN KEY (person_b_id) REFERENCES persons (id) ON DELETE CASCADE;

ALTER TABLE entity_sources ADD CONSTRAINT FK_419057D1953C1C61 FOREIGN KEY (source_id) REFERENCES sources (id) ON DELETE CASCADE;

ALTER TABLE entity_sources ADD CONSTRAINT FK_419057D155B127A4 FOREIGN KEY (added_by_id) REFERENCES users (id);

ALTER TABLE right_of_reply_requests ADD CONSTRAINT FK_E4D86EE3217BBB47 FOREIGN KEY (person_id) REFERENCES persons (id) ON DELETE CASCADE;

ALTER TABLE parties ADD CONSTRAINT FK_4363180532C8A3DE FOREIGN KEY (organization_id) REFERENCES organizations (id) ON DELETE CASCADE;

ALTER TABLE organization_country ADD CONSTRAINT FK_9EA1412532C8A3DE FOREIGN KEY (organization_id) REFERENCES organizations (id) ON DELETE CASCADE;

ALTER TABLE organization_country ADD CONSTRAINT FK_9EA14125517CF5E3 FOREIGN KEY (country_iso_code) REFERENCES countries (iso_code) ON DELETE CASCADE;

ALTER TABLE organization_translations ADD CONSTRAINT FK_1502E9732C8A3DE FOREIGN KEY (organization_id) REFERENCES organizations (id) ON DELETE CASCADE;

ALTER TABLE persons ADD CONSTRAINT FK_A25CC7D3B03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id);

ALTER TABLE person_nationality ADD CONSTRAINT FK_E72ED4F8217BBB47 FOREIGN KEY (person_id) REFERENCES persons (id) ON DELETE CASCADE;

ALTER TABLE person_nationality ADD CONSTRAINT FK_E72ED4F8517CF5E3 FOREIGN KEY (country_iso_code) REFERENCES countries (iso_code) ON DELETE CASCADE;

ALTER TABLE person_translations ADD CONSTRAINT FK_25B93ACA217BBB47 FOREIGN KEY (person_id) REFERENCES persons (id) ON DELETE CASCADE;

ALTER TABLE positions ADD CONSTRAINT FK_D69FE57C217BBB47 FOREIGN KEY (person_id) REFERENCES persons (id) ON DELETE CASCADE;

ALTER TABLE positions ADD CONSTRAINT FK_D69FE57C32C8A3DE FOREIGN KEY (organization_id) REFERENCES organizations (id) ON DELETE CASCADE;

ALTER TABLE positions ADD CONSTRAINT FK_D69FE57C517CF5E3 FOREIGN KEY (country_iso_code) REFERENCES countries (iso_code) ON DELETE SET NULL;

ALTER TABLE positions ADD CONSTRAINT FK_D69FE57CB03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id);

ALTER TABLE memberships ADD CONSTRAINT FK_865A4776217BBB47 FOREIGN KEY (person_id) REFERENCES persons (id) ON DELETE CASCADE;

ALTER TABLE memberships ADD CONSTRAINT FK_865A477632C8A3DE FOREIGN KEY (organization_id) REFERENCES organizations (id) ON DELETE CASCADE;

ALTER TABLE memberships ADD CONSTRAINT FK_865A4776B03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id);

ALTER TABLE revisions ADD CONSTRAINT FK_89B12285DAB5A938 FOREIGN KEY (proposed_by_id) REFERENCES users (id);

ALTER TABLE revisions ADD CONSTRAINT FK_89B12285C69DE5E5 FOREIGN KEY (validated_by_id) REFERENCES users (id);