# MANDATORY EXECUTION DIRECTIVE — COMPLETE THE IMPLEMENTATION

You are resuming an existing implementation task after context compaction.

The original specification, the agreed implementation plan, previous decisions, and the persistent implementation checklist remain authoritative.

You have already spent multiple execution turns delivering isolated changes and returning intermediate reports while the overall implementation remained incomplete.

This behaviour must stop.

**Your responsibility is to deliver the entire working implementation, not a succession of partially implemented features and progress reports.**

You have full authorization to execute the entire agreed plan without requesting intermediate approval.

## 1. Immediate execution — no acknowledgment

Do not acknowledge this message.

Do not apologize.

Do not tell me that I am right.

Do not announce that you are resuming the implementation.

Do not explain what you intend to do.

Do not produce another implementation plan.

Do not repeat your previous findings.

Do not return another message explaining that the implementation remains incomplete.

Your next action must be a concrete tool call that advances the implementation.

Recover the persistent checklist, inspect only what is necessary to resume the work, and immediately proceed with implementation.

**When actionable work remains, execute it instead of describing it.**

## 2. Absolute prohibition on backward compatibility

This is an intentional architectural replacement.

**NO BACKWARD COMPATIBILITY.**

Do not preserve obsolete architectures, data structures, interfaces, configuration formats, catalogues, or consumers for compatibility purposes.

Specifically:

- No legacy catalogue adapters.
- No compatibility wrappers.
- No fallback to obsolete catalogue classes.
- No dual catalogue architecture.
- No legacy configuration readers.
- No deprecated configuration aliases.
- No transitional runtime data sources.
- No parallel pricing implementations.
- No duplicated business logic.
- No legacy API contracts maintained solely for compatibility.
- No obsolete classes retained just because existing consumers still reference them.
- No unnecessary migration infrastructure intended to support obsolete application versions.

Existing consumers must be migrated to the new architecture.

If `MandateCatalog`, `ServiceCatalog`, or any other obsolete catalogue implementation still powers existing routes, migrate those routes.

If a consumer depends on an obsolete interface, update the consumer.

If an existing configuration format conflicts with the new architecture, replace the configuration format and update all its consumers.

If obsolete code becomes unnecessary after migration, remove it.

Do not introduce compatibility layers to avoid doing the actual migration.

A one-time data import or migration is acceptable when necessary to preserve existing business data. However, the old catalogue must not remain a runtime dependency, permanent initialization dependency, fallback source, or secondary source of truth.

Preserve the actual business information and intended application functionality. Do not preserve obsolete implementation decisions.

**Migrate, replace, verify, and remove. Do not adapt the new architecture to accommodate the obsolete one.**

## 3. Configuration conventions

Configuration files must be properly designed, documented, and usable.

Do not introduce meaningless empty configuration files into the repository without providing useful examples.

For configuration that requires deployment-specific values, credentials, partner registration, environment-specific settings, or private information, prefer explicit example configuration files.

For example:

- `config/partners.example.php`
- Other `*.example.php` files wherever appropriate.

Example configuration files must contain realistic, structurally valid example data demonstrating how the configuration is intended to be used.

Example entries may be commented out when appropriate.

For instance, a partner configuration example should demonstrate the actual supported structure for:

- Partner identifiers.
- Partner names.
- Enabled or disabled status.
- Credential hashes or secure credential references.
- Authorized domains.
- Offering access restrictions.
- Partner-specific pricing rules, where relevant.
- Other supported partner settings.

The example must match the actual implemented configuration schema. Do not invent configuration keys that the application does not consume.

Do not consider the following sufficient documentation:

```php
<?php declare(strict_types=1);

return [];
```

An empty runtime registry may be an appropriate secure default, but it does not replace a properly documented example configuration.

Provide useful examples that make the intended configuration immediately understandable to a developer or administrator.

Use clearly fictitious example values. Never commit actual credentials, production secrets, or sensitive partner information.

Ensure that local configuration files containing sensitive or deployment-specific information are appropriately excluded from version control.

If the application needs an actual configuration file at runtime, provide a clear initialization mechanism consistent with the existing repository conventions.

Missing required configuration must produce appropriate, explicit behaviour. Security-sensitive configuration must fail closed.

Do not silently fall back to obsolete configuration files.

Do not support two configuration formats for backward compatibility.

**Use clean configuration conventions from the beginning. Replace obsolete configurations and update their consumers.**

All technical documentation, code comments, identifiers, and configuration examples must be in English.

## 4. Execution loop — mandatory

Use the persistent implementation checklist as your execution queue.

Repeat the following process:

1. Select unfinished requirements.
2. Implement the required functionality.
3. Connect it to its actual consumers.
4. Remove obsolete implementations made unnecessary by the changes.
5. Run relevant automated and functional tests.
6. Correct failures and regressions.
7. Update the persistent checklist with actual verification evidence.
8. Immediately select the next unfinished requirements.

Do not return control to me between these steps.

Do not stop after completing one item.

Do not stop after completing one file.

Do not stop after completing one component.

Do not stop after completing one workstream.

Do not stop after running PHP syntax checks.

Do not stop because a convenient intermediate milestone has been reached.

**After completing an individual task, immediately proceed to the next actionable task.**

Repeat this process until the complete definition of done has been satisfied.

## 5. Parallel execution and agent coordination

Use multiple agents when the available tooling supports it and parallel execution provides a meaningful benefit.

Do not merely announce that agents will be used. Actually invoke them.

Assign independent workstreams with clear ownership and minimal conflicting file modifications.

Relevant workstreams include:

- Unified offering model and catalogue.
- Database persistence and publication.
- Catalogue administration.
- Complete migration of existing consumers.
- Pricing engine and currency handling.
- Partner configuration, authentication, and authorization.
- Partner distribution API.
- Installable distribution client package.
- Offering detail pages and relationships.
- Catalogue discovery and recommendations.
- Optional business journey questionnaire.
- Shortlist and commercial request integration.
- Webkernel-native frontend and partials.
- Automated tests and regression verification.
- Removal of obsolete implementations.
- Independent completion audit.

Do not waste time waiting for one agent if other independent implementation tasks can proceed.

Review the actual changes produced by each agent.

Integrate their work, resolve conflicts, verify behaviour, and immediately assign additional unfinished work where appropriate.

Delegation is not completion.

An agent claiming that its work is complete is not sufficient evidence.

The actual repository and verified application behaviour determine completion.

## 6. Unified catalogue — complete the migration

Implement the agreed single authoritative offering catalogue.

The unified model must power every relevant consumer, including:

- The services marketplace.
- Catalogue listings.
- Offering detail pages.
- Recommendations.
- Shortlist.
- Commercial requests.
- Pricing displays.
- Administration.
- Partner distribution.
- API endpoints.
- Other existing catalogue consumers discovered in the repository.

Preserve existing business data, multilingual content, prices, classifications, and relevant relationships during migration.

Offering types must remain independent of billing models.

Support complementary offerings, extensions, maintenance, follow-up, and relationship-specific visibility.

An extension restricted to its parent offering must not appear in inappropriate standalone catalogue listings.

Migrate all necessary data into the authoritative persistence layer.

Do not leave SQLite or any other selected persistence layer permanently dependent on legacy catalogue classes for initialization or operation.

Once the migration has been completed and verified, remove obsolete catalogue implementations and transformations.

Search the repository for remaining references and consumers.

Resolve them.

**A new database containing offerings does not constitute a unified catalogue if the application still reads from the old catalogues.**

**A new `Offering` model does not constitute a completed migration if existing routes and views still consume obsolete data structures.**

**NO BACKWARD COMPATIBILITY. Complete the migration.**

## 7. Persistence and administration

Implement the complete agreed persistence and administration workflow.

This includes:

- Persistent offering records.
- Multilingual contents.
- Taxonomies and classifications.
- Offering relationships.
- Structured price components.
- Partner-specific rules.
- Draft management.
- Preview.
- Revisions.
- Publication.
- Published catalogue snapshots.
- Appropriate initialization and migration mechanisms.
- Relevant backup and operational documentation.

The administration interface must be genuinely functional and integrated with the authoritative catalogue.

Use existing Webkernel capabilities and conventions.

Do not create an administration interface that merely displays static data or provides forms without functional persistence.

Verify the complete workflow from editing an offering to publishing it and consuming the published revision.

## 8. Pricing and commercial consistency

Complete the agreed pricing architecture.

Support all required pricing models, including offerings with percentage-based components that have no fixed monetary amount.

Support multiple price components within a single offering.

Implement the agreed calculation order:

Catalogue price in MAD → partner rule → selected options → display currency conversion.

Ensure that:

- Catalogue cards display correct prices.
- Detail pages display consistent prices.
- Recommendations display consistent prices.
- Shortlist uses the same pricing logic.
- Partner API responses use authorized partner pricing.
- Commercial requests preserve the relevant pricing context.
- Quotes preserve the applicable amounts and revisions.
- Authoritative prices are validated server-side.
- Partner remuneration and Numerimondes revenue are separated from customer-facing prices.
- Commercially sensitive information is protected.

Do not duplicate pricing calculations across controllers, views, JavaScript, API handlers, or catalogue implementations.

Correcting the import of percentage-based offerings is one implementation task. It does not constitute completion of the pricing architecture or the overall catalogue migration.

**Implement the entire pricing workflow and verify its actual consumers.**

## 9. Partner distribution and package

Complete the agreed distribution architecture, including the `numerimondes/offerings` package and the appropriate distribution client.

The partner API must consume the authoritative published catalogue.

Implement and verify:

- Partner authentication.
- Authorization.
- Allowed offering restrictions.
- Domain restrictions as a complementary control.
- Partner-specific pricing.
- Secure credential handling.
- API responses and error handling.
- Catalogue revisions.
- ETag handling.
- Local caching.
- Atomic snapshot updates.
- Last-known-valid snapshot recovery.
- Partner-specific cache isolation.
- Commercial request attribution.
- Server-side pricing validation.

Provide realistic example configuration and integration documentation.

Implement and verify a representative partner integration in the available environment.

Do not claim that an external production integration has been deployed unless it has actually been deployed and verified.

Do not leave the API connected to obsolete catalogues.

Do not introduce compatibility endpoints or legacy configuration support.

## 10. Catalogue discovery and user journey

Complete the agreed services marketplace experience.

The complete catalogue must remain accessible without answering questions.

Implement:

- The two initial questions.
- Recommendations from partial answers.
- A separate recommended offerings section.
- Explicit cross-industry offering classification.
- Education & Training.
- The optional business journey questionnaire.
- The agreed choices and ranges.
- Recommendation ranking.
- Reset functionality.
- Catalogue filtering.
- Consistent shortlist integration.
- Appropriate aggregate analytics.

Ensure that answering questions does not unnecessarily hide the complete catalogue.

Ensure that recommendations are actually connected to the authoritative catalogue and not generated from obsolete or duplicated data.

Verify the real user journey from catalogue discovery to offering details, complementary offerings, shortlist, and commercial interest.

## 11. Views and Webkernel integration

Complete the agreed view decomposition.

Use coherent page-specific partials and appropriately located shared partials.

Pass inclusion data explicitly.

Keep reusable components for genuinely reusable elements.

Separate business logic and data preparation from presentation.

Reuse existing Webkernel primitives, components, styles, helpers, and conventions.

Do not introduce a parallel frontend architecture unnecessarily.

Preserve the multilingual architecture.

Preserve correct currency handling.

Verify desktop, mobile, keyboard navigation, and relevant accessibility behaviour.

Follow the agreed editorial principles, including sprezzatura, concision, precision, and clarity.

Keep all code comments in English.

Do not add unnecessary comments explaining obvious code.

## 12. Architectural quality

Keep the implementation clean, coherent, maintainable, and appropriate to the existing repository.

Avoid:

- Unnecessary abstraction layers.
- Overengineering.
- Duplicated sources of truth.
- Duplicated business logic.
- Unnecessary dependencies.
- Placeholder implementations.
- Fake integrations.
- Empty scaffolding presented as finished functionality.
- Unused classes.
- Dead routes.
- Unused configuration files.
- Obsolete adapters.
- Temporary code left indefinitely.
- Unrelated refactoring.
- Destructive modifications to unrelated existing work.

Inspect existing repository conventions and reuse them when appropriate.

You are authorized to make reasonable technical decisions autonomously.

Do not interrupt execution to request approval for ordinary implementation decisions.

## 13. Verification — syntax checks are not sufficient

PHP syntax validation is necessary but does not establish functional correctness.

A successful `php -l` result only establishes that PHP can parse the relevant file.

It does not establish that:

- The application can execute the code.
- Dependencies are correctly resolved.
- Routes are connected.
- The database works.
- Data migration is complete.
- Prices are correct.
- Authorization works.
- Administration workflows are functional.
- API responses are correct.
- Partners receive the appropriate catalogue.
- Existing consumers have been migrated.
- The user interface behaves as intended.

Run meaningful automated tests and functional checks.

Exercise actual application entry points whenever the environment permits.

Test representative workflows end to end.

Test relevant failure scenarios.

Verify data consistency before and after migration.

Add regression tests for the introduced functionality.

Fix discovered defects and rerun the relevant tests.

Do not report isolated syntax checks as meaningful completion evidence for the entire project.

**Implementation without integration is incomplete.**

**Integration without verification is incomplete.**

**A passing syntax check is not an acceptance test.**

## 14. Independent completion audit

When the checklist appears complete, perform an independent audit before returning a final response.

Preferably assign this audit to a separate agent.

The auditor must examine the original specification, the agreed implementation plan, and the actual repository.

The audit must actively identify:

- Forgotten requirements.
- Incomplete functionality.
- Disconnected implementations.
- Remaining legacy catalogue consumers.
- Backward compatibility mechanisms.
- Obsolete configurations.
- Missing example configurations.
- Duplicate business logic.
- Duplicate data sources.
- Incorrect pricing.
- Security problems.
- Missing authorization checks.
- Unverified integrations.
- Missing translations.
- Broken routes.
- Unreachable functionality.
- Regressions.
- Inadequate tests.
- Incorrect completion claims.

Treat every valid audit finding as additional implementation work.

Fix the findings.

Rerun the relevant tests.

Repeat the audit when necessary.

Do not simply produce a list of findings and return it to me.

**Resolve the findings instead of transferring the remaining work back to me.**

## 15. Context compaction and execution continuity

Maintain a concise, persistent implementation checklist in the repository.

The checklist must contain enough information to resume execution without rediscovering the entire project.

Record:

- Original requirements.
- Relevant architectural decisions.
- Completed tasks.
- Unfinished tasks.
- Actual verification evidence.
- Remaining dependencies.
- Genuine external blockers.
- The next actionable implementation task.

Do not turn checklist maintenance into a separate project.

Keep it useful, concise, and current.

If context is compacted, recover the checklist and resume immediately.

Do not restart the planning phase.

Do not repeat already completed repository investigations unless new evidence makes them necessary.

If a hard execution limit genuinely forces termination, preserve the precise continuation state before stopping.

The next execution must be able to resume directly with implementation.

## 16. Communication policy — strictly enforced

I am not interested in repeated messages such as:

- "I am resuming the implementation."
- "I have inspected the repository."
- "I am launching parallel workstreams."
- "The checklist is in place."
- "I have implemented a first connection."
- "The implementation is not yet complete."
- "The catalogue migration remains the priority."
- "I have corrected one issue and verified PHP syntax."

These messages have repeatedly consumed execution turns without delivering the completed project.

Do not voluntarily end your execution with another message of this kind.

Minimize conversational output.

Maximize concrete implementation, integration, testing, correction, and verification.

Use your available execution time to modify and verify the actual application.

If your environment requires progress updates, keep them minimal and continue executing immediately afterward. A progress update must never become a voluntary termination point.

**Do not describe the next task when you can execute it.**

**Do not describe a fix when you can implement it.**

**Do not describe a remaining migration when you can complete it.**

**Do not return an intermediate report when you can continue working.**

## 17. Mandatory termination rules

Before producing a final response, evaluate the following conditions:

- Does an unfinished actionable requirement remain?
- Does an existing consumer still use an obsolete catalogue?
- Does the application still depend on legacy catalogue initialization?
- Does backward compatibility code remain?
- Does a required configuration lack a useful example?
- Does an implemented feature remain disconnected?
- Does a required integration remain unverified?
- Does a relevant test fail?
- Does an audit finding remain unresolved?
- Can another meaningful implementation or verification action be performed?

If the answer to any of these questions is yes, continue execution.

Your next action must be another meaningful tool call, not a final response.

The only acceptable reasons to terminate are:

1. The entire implementation satisfies the agreed definition of done.
2. A genuine hard execution limit or external constraint prevents further actionable work, after all independent work that remains possible has been addressed.

Do not manufacture artificial stopping points.

Do not impose arbitrary iteration limits.

Do not treat intermediate milestones as final deliverables.

## 18. Final delivery

Only after completing the implementation and verification, provide a consolidated report containing:

- What has actually been implemented.
- The resulting architecture.
- Important files and packages created, modified, or removed.
- Confirmation that obsolete catalogue consumers have been migrated.
- Confirmation that obsolete implementations and compatibility layers have been removed.
- Configuration conventions and available example files.
- Database migrations and initialization instructions.
- Catalogue administration instructions.
- Partner integration instructions.
- Tests performed and their actual results.
- Independent audit results.
- Any genuine external blockers, with precise evidence.

Do not provide another proposal.

Do not provide another plan.

Do not return another list of tasks that you could have implemented yourself.

# FINAL REMINDER

NO BACKWARD COMPATIBILITY.

NO LEGACY RUNTIME DEPENDENCIES.

NO PERMANENT LEGACY INITIALIZATION DEPENDENCIES.

NO DUPLICATED CATALOGUES.

NO UNDOCUMENTED EMPTY CONFIGURATIONS.

USE PROPER `*.example.php` CONFIGURATION FILES WITH MEANINGFUL EXAMPLES.

NO PLACEHOLDER IMPLEMENTATIONS.

NO DISCONNECTED FEATURES.

NO INTERMEDIATE FINAL REPORTS.

NO REPEATED PLANNING.

NO UNNECESSARY APPROVAL REQUESTS.

NO VOLUNTARY STOPPING WHILE ACTIONABLE WORK REMAINS.

IMPLEMENT.

INTEGRATE.

TEST.

FIX.

REMOVE OBSOLETE CODE.

AUDIT.

REPEAT UNTIL COMPLETE.

You already have authorization to perform the entire implementation.

Do not acknowledge this instruction.

Your next action must be a tool call that advances the implementation.

EXECUTE NOW.
