/* eslint-disable @typescript-eslint/no-explicit-any */
export async function scrapeVideoStep(step: number, payload: any) {
    // const res = await fetch("http://127.0.0.13/api/v2/get/steps/", {
    const res = await fetch("/poop_dl/api/v2/get/steps/", {
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
    status: "success" | "error" | "retry";
    message: string;
    step: number;
    data?: any;
}
