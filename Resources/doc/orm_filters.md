Using Doctrine ORM Filters
==========================

Allow to build automatically a Doctrine SQL Filter based on the Sonatra
ACL Rules, to filter the result of query.

But also, to clean the values of object fields that the current user
doesn't have the access permissions (and restore the empty values by the
values in database before persist.

> This feature require `doctrine/orm` dependency.

### Configure your application's config.yml

Add the following configuration to your `config.yml`.

```yaml
# app/config/config.yml
sonatra_security:
    doctrine:
        orm:
            object_filter_voter:   true # Enable the filter voter for ORM Object
            listener:
                acl_filter_fields: true # For clean value of object fields on post load and restore value on presist defined by ACLs
            filter:
                rule_filters:      true # Load the default ORM Filters of ACL Rules

doctrine:
    orm:
        entity_managers:
            default:
                filters:
                    sonatra_acl:
                        # Enable the Doctrine SQL Filter for Sonatra Rule Filters
                        class:   Sonatra\Bundle\SecurityBundle\Doctrine\ORM\Filter\AclFilter
                        enabled: true
```

### Regarding the AclVoter

It is used indirectly by Doctrine ORM. It is not used by the AclFilter,
but by the Doctrine event `PreLoad` (used in Doctrine UnitOfWorks). The
listener will get the list of objects retrieve by Doctrine, preload the
ACLs for all retrieved entities, and checked with the AclManager (so
with the Voter) if each field is authorized in READ.

### Regarding the ACL Filter Fields

The cleaning of the fields is performed in the `Unit of Work` of
Doctrine via the listeners `postLoad` and `onFlush`, see
[AclListener](https://github.com/sonatra/SonatraSecurityBundle/blob/master/Doctrine/ORM/Listener/AclListener.php)
and [AclObjectFilter](https://github.com/sonatra/SonatraSecurityBundle/blob/master/Acl/Domain/AclObjectFilter.php).

### Regarding the RuleDefinition and RuleFilterDefinition

- `RuleDefinition` is used by the `AclVoter`
- `RuleFilterDefinition` is used by the `AclFilter` for Doctrine ORM

If the `RuleDefinition` is created, a `RuleFilterDefinition` must also
be created (with the same name).

### Regarding the ORM Pagination

They are not in the result of Doctrine query. In the same way that if
you add a selection criteria to your ORM request (that's what doing the
AclFilter).

**Example:**

You have 1000 entities, A user can only access to 500 entities (1 of 2),
the user makes a request with a pagination with 50, you will have:

**Request size:** 50
**Total size:** 500
**Page number:** 1
**Total page:** 10

> The fields of the objects of the ORM Query will be cleaned, if the
user does not have access to these fields in reading.
