"use client"
import React from 'react'
import { useState } from "react";
import { cn } from '../../__components/Utils';
import { scrapeBulkUrl } from '../_utils/apiResolveBulk';
import type { ScrapeResponse } from '../_utils/apiResolveBulk';
import GetResults from './getResults';


const FormValidations = {
    url: {
        REGEX: /https?:\/\/(.+?)\/(f)\/([a-zA-Z0-9]+)/,
        ERROR_MESSAGE: "Please enter a valid PoopDL Folder URL.",
    },
};

/* eslint-disable @typescript-eslint/no-explicit-any */
/* eslint-disable @typescript-eslint/no-unused-vars */
/* eslint-disable @next/next/no-img-element*/
const formUi = (props: { className?: string }) => {
    const [errorMessage, setErrorMessage] = useState("");
    const [url, setUrl] = useState("");
    const [loading, setLoading] = useState(false);
    const [logMessage, setLogMessage] = useState("");
    const [resultsUrls, setResultsUrls] = useState<any>({});


    let payload: any = { url };


    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setLoading(true);
        setLogMessage("Memproses...");
        setErrorMessage("");
        setResultsUrls([]);
        if (loading) return;

        if (!url) {
            setErrorMessage("Please enter a valid URL.");
            setLoading(false);
            return;
        }

        if (!FormValidations.url.REGEX.test(url)) {
            setErrorMessage(FormValidations.url.ERROR_MESSAGE);
            setLoading(false);
            return;
        }

        try {
            const res = scrapeBulkUrl(payload);
            res.then((data: ScrapeResponse) => {
                if (data.status === "error") {
                    setErrorMessage(data.message);
                    setLoading(false);
                    return;
                }
                setLogMessage(data.message);
                setLoading(false);
                console.log(data.data);
                setResultsUrls(data.data);
            });
        } catch (error) {
            setErrorMessage("An error occurred while processing the URL.");
            setLoading(false);
        }
        setUrl("");
    };

    return (

        <div className={cn("w-full space-y-2", props.className)}>
            {errorMessage ? (
                <p className="h-4 text-sm text-red-500 sm:text-start">{errorMessage}</p>
            ) : (
                <div className="h-4"></div>
            )}
            <form className="w-full space-y-2" onSubmit={handleSubmit}>
                <div className="flex w-full items-center space-x-2">
                    <input
                        type="text"
                        placeholder="Paste your PoopHD Folder URL here..."
                        className="file:text-foreground placeholder:text-muted-foreground selection:bg-primary selection:text-primary-foreground dark:bg-input/30 border-input flex h-9 w-full min-w-0 rounded-md border bg-transparent px-3 py-1 text-base shadow-xs transition-[color,box-shadow] outline-none file:inline-flex file:h-7 file:border-0 file:bg-transparent file:text-sm file:font-medium disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive"
                        value={url}
                        onChange={(e) => setUrl(e.target.value)}
                    />
                    <button
                        type="submit"
                        disabled={loading || !url}
                        className="[&_svg:not([class*='size-'])]:size-4 [&_svg]:pointer-events-none [&_svg]:shrink-0 aria-invalid:border-destructive aria-invalid:ring-destructive/20 bg-teal-500 dark:aria-invalid:ring-destructive/40 dark:bg-teal-700 dark:hover:bg-teal-600 disabled:opacity-50 disabled:pointer-events-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 font-medium gap-2 h-9 has-[>svg]:px-3 hover:bg-teal-600 inline-flex items-center justify-center outline-none px-4 py-2 rounded-md shadow-xs shrink-0 text-sm text-white transition-all whitespace-nowrap"
                        onClick={handleSubmit}
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="lucide lucide-download h-4 w-4"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" x2="12" y1="15" y2="3"></line></svg>
                        {loading ? "Memproses..." : "Fetch URL"}
                    </button>
                </div>
            </form>
            {logMessage !== "" && !errorMessage && (
                <div className="flex items-center justify-center w-full h-10 mt-2 bg-gray-100 rounded-md shadow-md dark:bg-gray-800">
                    <svg className="w-6 h-6 text-gray-500 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v1m0 14v1m8.485-11.485l-.707.707M5.515 18.485l-.707-.707M20 12h1m-14 0H5m11.485 8.485l-.707-.707M7.515 5.515l-.707.707" /></svg>
                    <span className="ml-2 text-sm text-gray-700">{logMessage}</span>
                </div>
            )}

            {resultsUrls && resultsUrls.length > 0 && (
                <div className="flex flex-col w-full mt-4 space-y-2">
                    <GetResults urls={resultsUrls} />
                </div>
            )}

        </div>


    )
}

export default formUi