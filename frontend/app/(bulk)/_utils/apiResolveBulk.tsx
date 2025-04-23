/* eslint-disable @typescript-eslint/no-explicit-any */
export async function scrapeBulkUrl(payload: any) {
    const res = await fetch("http://127.0.0.13/api/v2/bulk/", {
        // const res = await fetch("/poop_dl/api/v2/bulk/", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
        },
        body: JSON.stringify({ ...payload }),
    });

    if (!res.ok) throw new Error("Gagal melakukan request");
    return res.json();
}

export interface ScrapeResponse {
    status: "success" | "error";
    message: string;
    data?: {
        result?: {
            url: string;
            type: string;
        }[];
        url?: string;
        folder_title?: string;
    };
    logs?: any[];
}
