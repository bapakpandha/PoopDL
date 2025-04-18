"use client";
import React, { useState } from 'react'
import { cn } from '@/app/__components/Utils'

const FormValidations = {
    url: {
        REGEX: /^(https?:\/\/)?(www\.)?instagram\.com\/(p|reel)\/.+$/,
        ERROR_MESSAGE: "Please enter a valid Instagram URL (post or reel).",
    },
};

const poopDLForm = (props: { className?: string }) => {
    const [inputValue, setInputValue] = useState("");
    const [errorMessage, setErrorMessage] = useState("");

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!FormValidations.url.REGEX.test(inputValue)) {
            setErrorMessage(FormValidations.url.ERROR_MESSAGE);
        } else {
            setErrorMessage("");
            // Proceed with form submission logic
            console.log("Form submitted with URL:", inputValue);
        }
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
                        placeholder="Paste your PoopHD URL here..."
                        className="w-full rounded-md border border-gray-300 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        value={inputValue}
                        onChange={(e) => setInputValue(e.target.value)}
                    />
                    <button
                        type="submit"
                        className="rounded-md bg-blue-500 px-4 py-2 text-white hover:bg-blue-600"
                    >
                        Download
                    </button>
                </div>
            </form>
            <p className="text-sm text-gray-500">
                Fast, free, and no login required. Just paste the URL and download.
            </p>
            <p className="text-sm text-gray-500">
                By using this service, you agree to our terms of service.
            </p>
            <p className="text-sm text-gray-500">
                This service is not affiliated with PoopHD in any way.
                <br />
                Please respect copyright and only download content you have the right to use.
                <br />
                For more information, please visit our <a href="/terms" className="text-blue-500 hover:underline">terms of service</a> page.
                <br />
            </p>
        </div>
    );
};

export default poopDLForm;