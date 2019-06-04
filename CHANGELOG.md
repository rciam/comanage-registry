# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/)

## [RCIAM v3.1.1]

### Added
- Subject DN attribute in user's profile
- RC Auth (DN linking) Plugin:
  - Associate subject DN of certificate issued by RCauth to user profile
  - Implement as COmanage Organisational Identity Source 
  - Integrated as an OIDC(OpenID connect) client to the MasterPortal
- VO field in user's profile
- VOMS Provisioner Plugin:
  - Implemented as COmanage Provisioning plugin
  - handles the (de)provisioning of users’ participation in Collaborations or Groups in VOMS( Virtual Organization Membership Service) server
  - Interacts with VOMS server via the utilization of the user’s Subject DN retrieved from MasterPortal
- Add search functionality to group membership management page. Users can be filtered/sorted:
  - by Given Name
  - by Family Name
  - by Email
  - by Identifier
  - Alphabetically
- Add search functionality to groups page. Groups can be filtered/sorted:
  - by Name
  - by Description
- Add search functionality to enrollments flow page. Enrollments can be filtered/sorted by Name
- Add `hidden` functionality to Enroll page. The Admin can enable the functionality by changing the value of `Hide Enrollment Flow` field to true, in the config page of an Enrollment flow. By default the value is false/empty and all the configured Enrolment Flows will be displayed in `People->Enroll` page.
- Retrieve AuthenticatingAuthority and depict in CO Person's canvas/profile

### Changed
- Update email and subject DN when the user logs into registry
- Use new [EGI theme](https://github.com/EGI-Foundation/comanage-registry-themeegi)

### Fixed
- Prevent users from submitting multiple registration requests
- Handle multiple attribute values for email and subject DN on registration
- Pagination functionality added in order to handle any error(s) occurred while managing large group memberships
- Update default CO Person Role entries without linking to a COU if not applicable
- CO Person's email gets verified during the registration process
- Add global scope for `Localization` variables of the default CO, COManage. This CO is only accessible by the platform administors.
- Allow CO Person to view all Org Identities linked to his/her profile
