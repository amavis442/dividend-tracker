# 🧰 Code Extraction & Refactor Checklist

A step-by-step guide to safely refactor and extract code into dedicated services, helpers, or units — without breaking your app.

---

## 1. 🔍 Understand the Code’s Purpose
- [ ] What is this code doing?
- [ ] Is its behavior already covered by tests?
- [ ] Does it belong in a dedicated unit (e.g., service, resolver, helper)?

---

## 2. 🧠 Define Clear Boundaries
- [ ] Choose an expressive name for the new class or method
- [ ] Keep method signatures clean and predictable
- [ ] Avoid exposing internal state that consumers don’t need

_Example: `ExchangeRateResolver::getRateForCalendar(Calendar $calendar): float`_

---

## 3. 🧪 Write Unit Tests First or During Extraction
- [ ] Cover expected behavior
- [ ] Include edge cases and failure modes
- [ ] Copy or relocate tests if logic was previously inline

---

## 4. 🔌 Swap In New Code at Call Sites
- [ ] Replace old inline logic with calls to your new unit
- [ ] Keep input/output contracts unchanged unless you're refactoring broadly
- [ ] Inject dependencies via constructor (avoid `new`, avoid service locator)

---

## 5. ⚙️ Update Service Configuration
- [ ] Register the new class for autowiring (if needed)
- [ ] Confirm that services.yaml or PHP attributes reflect correct visibility

---

## 6. 🧪 Run Integration & Functional Tests
- [ ] Run `Unit`, `Functional`, and `Project` test suites
- [ ] Check view models, controllers, and listeners for regressions
- [ ] Update any mock setups that reference the old logic

---

## 7. 📖 Document & Celebrate
- [ ] Update docblocks or comments if ownership or behavior changed
- [ ] Add README or usage notes if reused across contexts
- [ ] High five yourself — your codebase is now cleaner, leaner, and testable 🎉

