/**
 * Reuse the public profile's server-provided canonical URL. No route or host
 * is hard-coded, so this also works with a custom domain in the future.
 */
export function serviceBookingLink(publicUrl: string, serviceIds: number[]): string {
    if (serviceIds.length === 0 || serviceIds.length > 20 ||
        new Set(serviceIds).size !== serviceIds.length ||
        serviceIds.some(id => !Number.isSafeInteger(id) || id < 1)) {
        throw new Error("Выберите от 1 до 20 различных услуг.");
    }
    const url = new URL(publicUrl);
    // Clear any stale filters/selection from an incoming link.
    url.search = "";
    url.hash = "";
    if (serviceIds.length === 1) {
        url.searchParams.set("service", String(serviceIds[0]));
    } else {
        url.searchParams.set("selection", "1");
        for (const id of serviceIds) {
            url.searchParams.append("services[]", String(id));
        }
    }
    return url.toString();
}
