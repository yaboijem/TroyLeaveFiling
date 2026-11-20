-- Test-only Troy tables. Never deploy this file to the live database.

CREATE TABLE IF NOT EXISTS employeefiletbl (
  idno VARCHAR(100) NOT NULL,
  employeename VARCHAR(255) NOT NULL,
  deptcode VARCHAR(50) NOT NULL DEFAULT '',
  divcode VARCHAR(50) NOT NULL DEFAULT '',
  position VARCHAR(255) NOT NULL DEFAULT '',
  employmentstatus VARCHAR(100) NOT NULL DEFAULT '',
  email VARCHAR(100) NOT NULL,
  active VARCHAR(50) NOT NULL DEFAULT 'YES',
  usertype VARCHAR(20) NULL,
  password VARCHAR(255) NOT NULL,
  session_token VARCHAR(255) NULL,
  last_login TIMESTAMP NULL DEFAULT NULL,
  imagesrc VARCHAR(255) NOT NULL DEFAULT '',
  PRIMARY KEY (idno),
  UNIQUE KEY uq_emp_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS departmentfiletbl (
  deptcode VARCHAR(50) NOT NULL,
  deptname VARCHAR(255) NULL,
  PRIMARY KEY (deptcode)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
