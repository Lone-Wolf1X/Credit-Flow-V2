CREATE TABLE IF NOT EXISTS `document_components` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `code` varchar(100) NOT NULL UNIQUE,
    `html_content` longtext,
    `description` text,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- Insert a sample component (Collateral Table)
INSERT INTO
    `document_components` (
        `name`,
        `code`,
        `html_content`,
        `description`
    )
VALUES (
        'Standard Collateral Table',
        'COLLATERAL_TABLE',
        '<table border="1" cellpadding="5" cellspacing="0" style="width:100%; border-collapse:collapse;">
  <thead>
    <tr style="background-color:#f0f0f0;">
      <th><strong>S.N.</strong></th>
      <th><strong>Owner Name</strong></th>
      <th><strong>Plot No</strong></th>
      <th><strong>Area</strong></th>
      <th><strong>Location</strong></th>
    </tr>
  </thead>
  <tbody>
    <!-- Loop Example: {{#collaterals}} -->
    <tr>
      <td>{{sn}}</td>
      <td>{{owner_name}}</td>
      <td>{{plot_no}}</td>
      <td>{{area}}</td>
      <td>{{location}}</td>
    </tr>
    <!-- {{/collaterals}} -->
  </tbody>
</table>',
        'Standard collateral table with owner, plot, area details.'
    );