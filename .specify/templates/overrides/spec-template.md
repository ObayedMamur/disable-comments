# Feature Specification: [FEATURE NAME]

**Feature**: `[###-feature-name]`
**Created**: [DATE]
**Status**: Draft
**Type**: module
**Input**: User description: "$ARGUMENTS"

## User Scenarios & Testing *(mandatory)*

<!--
  User stories should be PRIORITIZED as user journeys ordered by importance.
  Each user story/journey must be INDEPENDENTLY TESTABLE.
  Assign priorities (P1, P2, P3, etc.) to each story.
-->

### User Story 1 - [Brief Title] (Priority: P1)

[Describe this user journey in plain language]

**Why this priority**: [Explain the value and why it has this priority level]

**Independent Test**: [Describe how this can be tested independently]

**Acceptance Scenarios**:

1. **Given** [initial state], **When** [action], **Then** [expected outcome]
2. **Given** [initial state], **When** [action], **Then** [expected outcome]

---

### User Story 2 - [Brief Title] (Priority: P2)

[Describe this user journey in plain language]

**Why this priority**: [Explain the value and why it has this priority level]

**Independent Test**: [Describe how this can be tested independently]

**Acceptance Scenarios**:

1. **Given** [initial state], **When** [action], **Then** [expected outcome]

---

[Add more user stories as needed, each with an assigned priority]

### Edge Cases

- What happens when [boundary condition]?
- How does system handle [error scenario]?

## Data Flow *(mandatory for full-stack features)*

<!--
  Describe how data moves through the system for this feature.
  For vertical-slice specs, cover: user action → React component → Redux action/saga →
  REST API → PHP endpoint → external API/database → response back through the stack.
-->

### Request Flow

1. **User Action**: [what the user does in the UI]
2. **React Component**: [which component handles it, what it dispatches]
3. **Redux**: [action type → saga/reducer → state change]
4. **REST API**: [endpoint, method, params]
5. **PHP Handler**: [which class/method processes it]
6. **External**: [Templately API call, database query, etc.]
7. **Response**: [how data flows back to UI]

### State Shape

```js
// Relevant slice of Redux state for this feature
{
  key: 'value'
}
```

## Requirements *(mandatory)*

<!--
  BEHAVIORAL REQUIREMENTS ONLY.

  Each FR must answer: "What can a user do, or what must the system guarantee?"
  Format: "The system MUST [user-observable outcome]"

  PROHIBITED in FRs:
  ✗ File paths (react-src/, includes/, *.js, *.php)
  ✗ Class names, function names, method signatures
  ✗ DOM IDs, CSS class names, HTML element selectors
  ✗ Internal variable names, action type constants
  ✗ Barrel export requirements ("X must re-export Y")
  ✗ Build tool configuration details
-->

### Functional Requirements

- **FR-001**: System MUST [specific capability]
- **FR-002**: System MUST [specific capability]

### Key Entities *(include if feature involves data)*

- **[Entity 1]**: [What it represents, key attributes without implementation]
- **[Entity 2]**: [What it represents, relationships to other entities]

## Existing Test Coverage *(mandatory)*

<!--
  Map ALL existing tests that cover this feature. This section ensures
  tests are preserved and accounted for during reimplementation.
  Group by test type.
-->

### E2E Tests (Playwright)

| Test File | What It Tests |
|-----------|---------------|
| `tests/e2e/specs/NNN-name.spec.js` | [description] |

### Unit Tests (PHP)

| Test File | What It Tests |
|-----------|---------------|
| `tests/unit/path/test-File.php` | [description] |

### Integration Tests (PHP)

| Test File | What It Tests |
|-----------|---------------|
| `tests/integration/test-File.php` | [description] |

### Unit Tests (Jest)

| Test File | What It Tests |
|-----------|---------------|
| `react-src/path/__tests__/file.test.js` | [description] |

### Test Gaps

- [Areas that lack test coverage and should be tested]

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: [Measurable metric]
- **SC-002**: [Measurable metric]
