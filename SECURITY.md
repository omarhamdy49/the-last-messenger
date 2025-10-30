# Security Policy

**The Last Messenger** - Security Guidelines and Vulnerability Reporting
**Version**: 1.0.0
**Last Updated**: 2025-10-29

---

## 🛡️ Overview

Security is a top priority for The Last Messenger platform. This document outlines our security policies, best practices, and procedures for reporting vulnerabilities.

---

## 📋 Table of Contents

1. [Supported Versions](#supported-versions)
2. [Security Features](#security-features)
3. [Best Practices](#best-practices)
4. [Reporting Vulnerabilities](#reporting-vulnerabilities)
5. [Security Configurations](#security-configurations)
6. [Data Protection](#data-protection)
7. [Incident Response](#incident-response)

---

## ✅ Supported Versions

We actively support and provide security updates for the following versions:

| Version | Supported          | End of Life |
| ------- | ------------------ | ----------- |
| 1.0.x   | ✅ Yes             | TBD         |
| 0.9.x   | ⚠️ Limited         | 2025-11-30  |
| < 0.9   | ❌ No              | Expired     |

**Recommendations**:
- Always use the latest stable version
- Apply security patches immediately
- Subscribe to security notifications
- Review changelogs for security fixes

---

## 🔒 Security Features

### Authentication & Authorization

#### Session Management
- **Laravel Sanctum**: Session-based authentication
- **Secure Cookies**: HttpOnly, Secure, SameSite flags
- **Session Timeout**: 2 hours (configurable)
- **Session Regeneration**: On login/logout
- **CSRF Protection**: Token validation on all POST requests

#### Password Security
- **Hashing**: bcrypt with cost factor 10
- **Requirements**:
  - Minimum 8 characters
  - At least 1 uppercase letter
  - At least 1 lowercase letter
  - At least 1 number
  - At least 1 special character
- **Validation**: Server-side enforcement
- **No Password**: Plain text passwords never stored

#### Role-Based Access Control (RBAC)
- **Spatie Permission Package**: Industry-standard RBAC
- **6 Predefined Roles**:
  1. super_admin
  2. admin
  3. editor
  4. researcher
  5. user
  6. guest
- **Fine-Grained Permissions**: Module-level control
- **Permission Checks**: On every sensitive operation

### Data Protection

#### Input Validation
- **Server-Side Validation**: Laravel Form Requests
- **Type Checking**: Strict PHP types
- **Sanitization**: HTML/SQL injection prevention
- **File Upload Validation**:
  - MIME type checking
  - File extension validation
  - Size limits enforced
  - Malware scanning (recommended)

#### SQL Injection Prevention
- **Eloquent ORM**: Parameterized queries
- **Query Builder**: Prepared statements
- **No Raw Queries**: Unless absolutely necessary
- **Validation**: All user inputs validated

#### XSS Prevention
- **Blade Escaping**: Automatic output escaping
- **Content Security Policy (CSP)**: Configured headers
- **Input Sanitization**: HTML tags stripped
- **Safe Rendering**: User content sanitized

#### CSRF Protection
- **Automatic Token Generation**: On all forms
- **Token Validation**: Middleware enforcement
- **SameSite Cookies**: CSRF token in cookie
- **Ajax Requests**: X-CSRF-TOKEN header required

### Network Security

#### HTTPS/SSL
- **Mandatory HTTPS**: Production requirement
- **TLS 1.2+**: Minimum protocol version
- **Strong Ciphers**: Modern cipher suites only
- **HSTS**: HTTP Strict Transport Security enabled
- **Certificate**: Valid SSL certificate required

#### Security Headers
```nginx
# X-Frame-Options
X-Frame-Options: SAMEORIGIN

# X-Content-Type-Options
X-Content-Type-Options: nosniff

# X-XSS-Protection
X-XSS-Protection: 1; mode=block

# Referrer-Policy
Referrer-Policy: no-referrer-when-downgrade

# Content-Security-Policy
Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' data:;

# Permissions-Policy
Permissions-Policy: geolocation=(), microphone=(), camera=()
```

#### Rate Limiting
- **Admin API**: 60 requests/minute per IP
- **Public API**: 120 requests/minute per IP
- **Login Attempts**: 5 attempts per 15 minutes
- **Throttle Middleware**: Laravel built-in
- **IP Blocking**: Automatic for abuse

### File Security

#### Upload Restrictions
- **Allowed Types**: JPG, PNG, WebP, PDF, MP4
- **Max Size**: 100MB
- **Validation**: MIME type and extension
- **Storage**: Outside public directory
- **Access Control**: Authenticated requests only

#### File Permissions
```bash
# Directories
755 (rwxr-xr-x)

# Files
644 (rw-r--r--)

# Storage & Cache
775 (rwxrwxr-x)

# .env file
600 (rw-------)
```

### Database Security

#### Connection Security
- **SSL/TLS**: Encrypted database connections
- **Strong Passwords**: 32+ character passwords
- **Limited Privileges**: Principle of least privilege
- **Separate Users**: Different users per environment

#### Data Encryption
- **At Rest**: Database encryption (MySQL InnoDB)
- **In Transit**: SSL/TLS connections
- **Sensitive Data**: Laravel encryption for PII
- **Key Management**: Secure key storage

#### Backup Security
- **Encrypted Backups**: AES-256 encryption
- **Secure Storage**: Off-site backup location
- **Access Control**: Limited backup access
- **Retention Policy**: 30 days, then encrypted deletion

---

## 🎯 Best Practices

### For Administrators

#### Account Security
✅ **Do**:
- Use strong, unique passwords
- Change default credentials immediately
- Enable two-factor authentication (when available)
- Limit admin account creation
- Review user permissions regularly
- Monitor activity logs
- Log out when finished

❌ **Don't**:
- Share admin credentials
- Use simple passwords
- Leave sessions unattended
- Grant unnecessary permissions
- Ignore security warnings

#### Server Security
✅ **Do**:
- Keep software updated
- Apply security patches promptly
- Use firewall (UFW/iptables)
- Enable fail2ban
- Monitor server logs
- Regular security audits
- Backup regularly
- Use SSH keys (not passwords)

❌ **Don't**:
- Expose unnecessary ports
- Use default configurations
- Ignore log warnings
- Skip updates
- Allow root SSH login

### For Developers

#### Code Security
✅ **Do**:
- Follow OWASP guidelines
- Use prepared statements
- Validate all inputs
- Escape all outputs
- Use CSRF tokens
- Implement rate limiting
- Log security events
- Handle errors gracefully
- Use security linters
- Review code for vulnerabilities

❌ **Don't**:
- Trust user input
- Use dynamic SQL queries
- Store passwords in plain text
- Expose sensitive data in errors
- Commit .env files
- Hardcode secrets
- Disable security features

#### Dependency Management
✅ **Do**:
- Keep dependencies updated
- Use Composer for PHP packages
- Review package licenses
- Audit dependencies for vulnerabilities
- Use `composer audit`
- Lock versions in production

❌ **Don't**:
- Use outdated packages
- Install untrusted packages
- Ignore security advisories

### For Users

#### Account Hygiene
✅ **Do**:
- Use strong passwords
- Change passwords regularly (90 days)
- Log out after use
- Report suspicious activity
- Keep email address current
- Review account activity

❌ **Don't**:
- Share login credentials
- Use public computers
- Save passwords in browser
- Click suspicious links
- Download unknown files

---

## 🚨 Reporting Vulnerabilities

### Security Contact

**Primary Contact**:
- 📧 Email: security@thelastmessenger.com
- 🔒 PGP Key: Available on request

**Alternative Contact**:
- 📧 Email: cto@thelastmessenger.com

### What to Report

Please report:
- ✅ SQL injection vulnerabilities
- ✅ XSS (Cross-Site Scripting) vulnerabilities
- ✅ CSRF bypass
- ✅ Authentication bypass
- ✅ Privilege escalation
- ✅ Information disclosure
- ✅ Remote code execution
- ✅ File upload vulnerabilities
- ✅ Session fixation
- ✅ Any security concern

Please don't report:
- ❌ Feature requests
- ❌ UI bugs
- ❌ Performance issues
- ❌ Documentation errors

### Reporting Process

1. **Email Security Team**:
   - Use security@thelastmessenger.com
   - Subject: [SECURITY] Brief description
   - Encrypt sensitive details with PGP (if possible)

2. **Include Details**:
   - Vulnerability description
   - Steps to reproduce
   - Impact assessment
   - Affected version(s)
   - Proof of concept (if safe)
   - Your contact information

3. **Expected Response**:
   - Acknowledgment within 24 hours
   - Initial assessment within 3 business days
   - Regular updates on progress
   - Credit in security advisories (if desired)

### What to Expect

**Our Commitment**:
- ✅ Acknowledge receipt quickly
- ✅ Keep you informed
- ✅ Fix verified issues promptly
- ✅ Credit reporters (with permission)
- ✅ Transparent communication

**Timeline**:
- **Critical**: Fix within 24-48 hours
- **High**: Fix within 7 days
- **Medium**: Fix within 30 days
- **Low**: Fix in next release

### Responsible Disclosure

We follow responsible disclosure practices:

**What We Ask**:
- Give us time to fix before public disclosure
- Don't exploit vulnerability beyond demonstration
- Don't access or modify user data
- Don't perform DoS attacks
- Don't disclose to others before fix

**What We Promise**:
- No legal action against good-faith researchers
- Credit in security advisories
- Timely fixes and communication
- Public acknowledgment of your contribution

### Bug Bounty Program

**Status**: Coming in 2026

**Rewards** (when launched):
- **Critical**: $500 - $2,000
- **High**: $200 - $500
- **Medium**: $50 - $200
- **Low**: $25 - $50

Join our waiting list: security@thelastmessenger.com

---

## ⚙️ Security Configurations

### Environment Variables

**Critical Security Settings** (`.env`):
```bash
# MUST be false in production
APP_DEBUG=false
APP_ENV=production

# Generate strong key
APP_KEY=base64:GENERATE_WITH_php_artisan_key:generate

# Session security
SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_DOMAIN=.yourdomain.com
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax

# Sanctum
SANCTUM_STATEFUL_DOMAINS=yourdomain.com,www.yourdomain.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=STRONG_PASSWORD_HERE

# Redis
REDIS_PASSWORD=STRONG_PASSWORD_HERE

# Mail (don't expose credentials)
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=your_username
MAIL_PASSWORD=STRONG_PASSWORD_HERE
MAIL_ENCRYPTION=tls
```

### Firewall Configuration

**UFW (Uncomplicated Firewall)**:
```bash
# Default policies
sudo ufw default deny incoming
sudo ufw default allow outgoing

# Allow SSH (change port if needed)
sudo ufw allow 22/tcp

# Allow HTTP/HTTPS
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# Allow MySQL (only from localhost)
sudo ufw allow from 127.0.0.1 to any port 3306

# Allow Redis (only from localhost)
sudo ufw allow from 127.0.0.1 to any port 6379

# Enable firewall
sudo ufw enable
```

### Fail2Ban Configuration

**Protect against brute force** (`/etc/fail2ban/jail.local`):
```ini
[DEFAULT]
bantime = 3600
findtime = 600
maxretry = 5

[sshd]
enabled = true
port = 22
logpath = /var/log/auth.log

[nginx-http-auth]
enabled = true
filter = nginx-http-auth
port = http,https
logpath = /var/log/nginx/error.log

[nginx-limit-req]
enabled = true
filter = nginx-limit-req
port = http,https
logpath = /var/log/nginx/error.log

[laravel]
enabled = true
filter = laravel
port = http,https
logpath = /var/www/the-last-messenger/storage/logs/laravel.log
maxretry = 3
```

### Database Hardening

**MySQL Security** (`/etc/mysql/mysql.conf.d/mysqld.cnf`):
```ini
[mysqld]
# Bind to localhost only
bind-address = 127.0.0.1

# Disable remote root login
skip-networking = 0

# Set secure file privileges
secure-file-priv = /var/lib/mysql-files

# Enable SSL
require_secure_transport = ON
ssl-ca = /etc/mysql/ca-cert.pem
ssl-cert = /etc/mysql/server-cert.pem
ssl-key = /etc/mysql/server-key.pem

# Log suspicious queries
log-error = /var/log/mysql/error.log
log-warnings = 2
```

---

## 🛡️ Data Protection

### GDPR Compliance

**User Rights**:
- ✅ Right to access (data export)
- ✅ Right to rectification (edit profile)
- ✅ Right to erasure (account deletion)
- ✅ Right to data portability (JSON export)
- ✅ Right to object (opt-out)

**Data Processing**:
- ✅ Lawful basis documented
- ✅ Data minimization
- ✅ Storage limitation
- ✅ Integrity and confidentiality
- ✅ Privacy by design

### Personal Data Handling

**Data We Collect**:
- Name
- Email address
- IP address (logs)
- User agent (logs)
- Activity logs
- Content created

**Data We Don't Collect**:
- Credit card information
- Social security numbers
- Precise geolocation
- Biometric data
- Health information

**Data Retention**:
- Active accounts: Indefinitely
- Deleted accounts: 30 days, then purged
- Logs: 90 days
- Backups: 30 days

### Encryption

**Data Encryption**:
- **At Rest**:
  - Database: InnoDB encryption
  - Files: Filesystem encryption
  - Backups: AES-256 encryption
- **In Transit**:
  - HTTPS/TLS 1.2+
  - Database connections: SSL/TLS
  - API calls: HTTPS only

---

## 🚨 Incident Response

### Security Incident Procedure

**1. Detection**:
- Monitor logs for anomalies
- Automated alerting systems
- User reports
- Security audits

**2. Assessment**:
- Severity determination
- Impact analysis
- Affected systems identified
- Root cause investigation

**3. Containment**:
- Isolate affected systems
- Block malicious IPs
- Revoke compromised credentials
- Disable vulnerable features

**4. Eradication**:
- Remove malicious code
- Patch vulnerabilities
- Update dependencies
- Clear backdoors

**5. Recovery**:
- Restore from clean backups
- Verify system integrity
- Re-enable services
- Monitor for recurrence

**6. Post-Incident**:
- Document incident
- Update security measures
- Notify affected users (if required)
- Review and improve procedures

### Contact During Incident

**Emergency Contact**:
- 📧 Email: security@thelastmessenger.com
- 📱 Phone: +1-XXX-XXX-XXXX (24/7 on-call)
- 🔒 Secure Channel: PGP encrypted email

**Information Needed**:
- Incident description
- Time of occurrence
- Affected systems/users
- Actions taken so far
- Evidence collected

---

## 📊 Security Audits

### Regular Audits

**Schedule**:
- **Code Review**: Every release
- **Dependency Audit**: Weekly
- **Penetration Testing**: Quarterly
- **Security Scan**: Monthly
- **Access Review**: Quarterly

**Tools Used**:
- Laravel Security Checker
- Composer Audit
- OWASP ZAP
- Snyk
- SonarQube

### Third-Party Audits

**Annual Security Audit**:
- Professional security firm
- Comprehensive assessment
- Penetration testing
- Vulnerability scanning
- Compliance review
- Detailed report

---

## 📚 Security Resources

### Guidelines & Standards

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [Laravel Security Best Practices](https://laravel.com/docs/security)
- [GDPR Compliance](https://gdpr.eu/)
- [NIST Cybersecurity Framework](https://www.nist.gov/cyberframework)
- [CIS Benchmarks](https://www.cisecurity.org/cis-benchmarks/)

### Training & Awareness

**For Team Members**:
- Security awareness training (quarterly)
- OWASP training materials
- Security newsletters
- Webinars and conferences
- Internal security workshops

**For Users**:
- Security best practices guide
- Phishing awareness
- Password management tips
- Safe browsing practices

---

## ✅ Security Checklist

### Pre-Deployment

- [ ] Change all default credentials
- [ ] Generate strong APP_KEY
- [ ] Set APP_DEBUG=false
- [ ] Configure HTTPS/SSL
- [ ] Set security headers
- [ ] Configure firewall
- [ ] Enable fail2ban
- [ ] Set proper file permissions
- [ ] Secure database connection
- [ ] Set Redis password
- [ ] Configure rate limiting
- [ ] Enable audit logging
- [ ] Set up monitoring
- [ ] Configure backups
- [ ] Review .env security
- [ ] Remove debug tools

### Post-Deployment

- [ ] Verify HTTPS works
- [ ] Test authentication
- [ ] Check permissions
- [ ] Review logs
- [ ] Test backups
- [ ] Monitor performance
- [ ] Check security headers
- [ ] Verify rate limiting
- [ ] Test error handling
- [ ] Review user accounts

### Ongoing

- [ ] Apply security updates
- [ ] Review access logs
- [ ] Monitor failed logins
- [ ] Audit user permissions
- [ ] Update dependencies
- [ ] Review security advisories
- [ ] Test backups
- [ ] Security training
- [ ] Vulnerability scans
- [ ] Penetration tests

---

## 🔔 Security Advisories

Subscribe to security updates:
- 📧 Email: security-updates@thelastmessenger.com
- 🐦 Twitter: @LastMessengerSec
- 📢 Security Page: https://thelastmessenger.com/security

**Past Advisories**:
- None yet (v1.0.0 is first release)

---

## 🤝 Security Team

**Security Officer**: security@thelastmessenger.com
**CTO**: cto@thelastmessenger.com
**Development Team**: dev@thelastmessenger.com

---

## ⚖️ Legal

### Vulnerability Disclosure Policy

This policy applies to security researchers and white-hat hackers who discover vulnerabilities in The Last Messenger platform.

**Safe Harbor**:
We will not pursue legal action against individuals who:
- Report vulnerabilities responsibly
- Don't exploit vulnerabilities maliciously
- Don't access/modify user data
- Follow our disclosure guidelines
- Act in good faith

**Out of Scope**:
- Social engineering attacks
- Physical attacks
- Denial of Service attacks
- Spam or phishing
- Third-party applications
- Testing on production without permission

---

## 📞 Questions?

For security questions or concerns:
- 📧 Security: security@thelastmessenger.com
- 📖 Documentation: https://docs.thelastmessenger.com/security
- 💬 Support: support@thelastmessenger.com

**Remember**: Security is everyone's responsibility. Report concerns promptly and follow best practices.

---

**Document Version**: 1.0.0
**Last Updated**: 2025-10-29
**Next Review**: 2026-01-29
**Maintained By**: The Last Messenger Security Team
