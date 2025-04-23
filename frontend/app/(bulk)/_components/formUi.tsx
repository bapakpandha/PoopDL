"use client"
import React from 'react'
import { useState } from "react";
import { cn } from '../../__components/Utils';
import { scrapeBulkUrl } from '../_utils/apiResolveBulk';
import type { ScrapeResponse } from '../_utils/apiResolveBulk';
import GetResults from './getResults';
import StatusDropdown from './dropdownButtonOption';


const FormValidations = {
    url: {
        REGEX: /https?:\/\/(.+?)\/(f)\/([a-zA-Z0-9]+)|.*justpaste.*/,
        ERROR_MESSAGE: "Please enter a valid PoopDL Folder URL or justpaste URL.",
    },
};

/* eslint-disable @typescript-eslint/no-explicit-any */
/* eslint-disable @typescript-eslint/no-unused-vars */
const FormUi = (props: { className?: string }) => {
    const [errorMessage, setErrorMessage] = useState("");
    const [url, setUrl] = useState("");
    const [loading, setLoading] = useState(false);
    const [logMessage, setLogMessage] = useState("");
    const [resultsUrls, setResultsUrls] = useState<any>({});
    const [bulkJenis, setBulkJenis] = useState("");
    const [isResultHidden, setIsResulthidden] = useState(true);

    interface BulkJenisChangeHandler {
        (newBulkJenis: string): void;
    }

    const handleBulkJenisChange: BulkJenisChangeHandler = (newBulkJenis) => {
        setBulkJenis(newBulkJenis);
    };

    const handleResultHiddenn = () => {
        setIsResulthidden(!isResultHidden);
    }

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

        const payload: any = {
            url: url,
            justpaste: bulkJenis === "Justpaste"
        };

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
                setResultsUrls(data.data?.result);
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
                        name='bulk_URL'
                        type="text"
                        placeholder="Paste your PoopHD Folder URL here..."
                        className="file:text-foreground placeholder:text-muted-foreground selection:bg-primary selection:text-primary-foreground dark:bg-input/30 border-input flex h-9 w-full min-w-0 rounded-md border bg-transparent px-3 py-1 text-base shadow-xs transition-[color,box-shadow] outline-none file:inline-flex file:h-7 file:border-0 file:bg-transparent file:text-sm file:font-medium disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive"
                        value={url}
                        onChange={(e) => setUrl(e.target.value)}
                    />

                    <StatusDropdown
                        onChange={handleBulkJenisChange}
                        isLoadings={loading}
                        buttonUrl={url}
                        handleSubmit={handleSubmit}
                    />
                </div>
            </form>
            {logMessage !== "" && !errorMessage && (
                <div className="flex items-center justify-center w-full h-10 mt-2 bg-gray-100 rounded-md shadow-md dark:bg-gray-800">
                    <svg className="w-6 h-6 text-gray-500 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v1m0 14v1m8.485-11.485l-.707.707M5.515 18.485l-.707-.707M20 12h1m-14 0H5m11.485 8.485l-.707-.707M7.515 5.515l-.707.707" /></svg>
                    <span className="ml-2 text-sm text-gray-700">{logMessage}</span>
                </div>
            )}

            {resultsUrls && resultsUrls.length > 0 && (
                <div className='flex flex-col items-center'>
                    <button
                        onClick={handleResultHiddenn}
                        className="mt-4 focus:outline-none text-white bg-red-700 hover:bg-red-800 focus:ring-4 focus:ring-red-300 font-medium rounded-lg text-sm px-5 py-2.5 me-2 mb-2 dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-red-900"
                    >
                        {isResultHidden ? "Show Result" : "Hide Result"}
                    </button>
                    <div className={`flex flex-col w-full mt-4 space-y-2 ${isResultHidden ? "hidden" : ""}`}>
                        <GetResults urls={resultsUrls} />
                    </div>
                </div>
            )}


        </div>


    )
}

export default FormUi