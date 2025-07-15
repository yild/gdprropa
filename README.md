# GLPI GDPRRoPA

Based on [DPO Register](https://github.com/karhel/glpi-dporegister) plugin, by Karhel Tmarr. Some code was included or is similar to DPO Register.


Registry of Processing Activities for GDPR - [GLPI](https://github.com/glpi-project/glpi/) Plugin

GDPR EU regulation require controllers (data administrators) to maintain Registry of Processing Activities (article 30 of GDPR) for each personal data processing.

This plugin adds to GLPI managment of Processing Activities Registry, can be maintained for each entity.

Main features:
* Processing activiity information
* Dictionary of personal data categories (can be nested)
* Multiple data subjects per record
* Data retention (legal basis, contract, other)
* Assign contracts, by selected contract type: processor, controller, thirdcountry, other, internal
* Multiple legal bases per record
* Dictionary of security measures (can be separate by entity)
* Assign software
* PDF output
* Each entity can have separated controller info: legal representative, DPO and name, different contract types
* Dashboard widget with number of Records per entity (and its sons).
* additional configuration


## Translation

Currently en_GB and pl_PL translations available.

## Documentation

### Installation

Install as normal plugin (currently no 9.5 GLPI Marketplace).

Plugin settings can be changed in Setup->Plugins->GDPR Records of Processing Activities. Also sample data can be injected.

### Right management

By default, super admin will have full access rights to GDPRRoPA. Additional profiles must be setup individually.

### Populate the dropdowns

Dropdowns (dictionaries), can be created for each and every (recursive) entity:

* Legal bases - can be of type:
  * Undefined,
  * GDPR Article,
  * Local law regulation (country, state, etc.),
  * International regulations (ie. treaties),
  * Controller internal regulations (ie. rules, procedures),
  * Other... as other than above.

* Categories of data subjects - employees, patients, clients, etc.

* Personal data category - categories of personal data ie (surname, firstname, addresses, ID number, blood type, etc.), can be hierarchical to group items in larger categories.

* Security measures - can be of type:
  * Organizational - ie. internal regulations, rules, procedures, etc.,
  * Physical - locks, fire systems, theft detection, cctv, etc.,
  * IT - firewalls, antyvirus apps, VLANs, authorisation by passwords and login, etc.

### Assign contracts to RoPA

First create five contract types (Setup->Dropdown->Contract Types) coresponding to:
* Processor contract,
* Joint controller contract,
* Thirdparty contract,
* Internal contract - this can be placehorder for between departments data transer,
* Other contract.

### Create Processing Activity

First set Legal Representative, Data Protection Officer, Controller Name for entity (Administration->Entities, GDPR Controller Info), at this moment contract types can be selected.

Management->GDPR Records of Processing Activities, will add RoPA for current active entity.

### Create PDF of RoPA

Management->GDPR Records of Processing Activities, top menu (printer icon) - will create PDF with RoPA of current active entity and its sons.
RoPA tab->Create PDF - PDF for current RoPA,
Administration->Entities, GDPR Controller Info - PDF for selected entity.

Global page settings can be set at Setup->Plugins->GDPR Records of Processing Activities.

## Contributing

* Open a ticket for each bug/feature so it can be discussed
* Follow [development guidelines](https://glpi-developer-documentation.readthedocs.io/en/latest/plugins/index.html)
* Refer to [GitFlow](https://git-flow.readthedocs.io/) process for branching
* Work on a new branch on your own fork
* Open a PR that will be reviewed by a developer
