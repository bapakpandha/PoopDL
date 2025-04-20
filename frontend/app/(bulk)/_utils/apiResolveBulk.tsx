/* eslint-disable @typescript-eslint/no-explicit-any */
export async function scrapeBulkUrl(payload: any) {
    const res = await fetch("http://127.0.0.13/api/v2/bulk/", {
    // const res = await fetch("/poop_dl/api/bulk/", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
        },
        body: JSON.stringify({...payload }),
    });

    if (!res.ok) throw new Error("Gagal melakukan request");
    return res.json();
}

export interface ScrapeResponse {
    status: "success" | "error";
    message: string;
    data?: any[];
    logs?: any[];
}
