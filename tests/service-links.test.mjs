import test from "node:test";
import assert from "node:assert/strict";
import { serviceBookingLink } from "../resources/js/service-links.ts";

test("single service link preselects the service on the public profile", () => {
    const url = new URL(serviceBookingLink("https://bosh.kg/m/azamat", [17]));
    assert.equal(url.pathname, "/m/azamat");
    assert.equal(url.searchParams.get("service"), "17");
    assert.equal(url.searchParams.has("selection"), false);
});

test("bundle links keep every selected service in its original order", () => {
    const url = new URL(serviceBookingLink("https://bosh.kg/m/azamat", [3, 5, 21]));
    assert.equal(url.searchParams.get("selection"), "1");
    assert.deepEqual(url.searchParams.getAll("services[]"), ["3", "5", "21"]);
});

test("link builder strips stale query parameters and fragments", () => {
    const link = serviceBookingLink("https://bosh.kg/m/azamat?date=2026-01-01#bio", [2]);
    assert.equal(new URL(link).search, "?service=2");
    assert.equal(new URL(link).hash, "");
});

test("link builder rejects empty, repeated, or invalid service ids", () => {
    for (const ids of [[], [1, 1], [0], [-1], [NaN], [2.5], Array.from({length:21}, (_,i)=>i+1)]) {
        assert.throws(() => serviceBookingLink("https://bosh.kg/m/azamat", ids));
    }
});
