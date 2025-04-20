/* eslint-disable @typescript-eslint/no-explicit-any */
export async function scrapeVideoStep(step: number, payload: any) {
    // const res = await fetch("http://127.0.0.13/api/get/steps/", {
    const res = await fetch("/api/get/steps/", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
        },
        body: JSON.stringify({ step, ...payload }),
    });

    if (!res.ok) throw new Error("Gagal melakukan request");
    return res.json();
}

export interface ScrapeResponse {
    status: "success" | "error";
    message: string;
    step: number;
    data?: any;
}
