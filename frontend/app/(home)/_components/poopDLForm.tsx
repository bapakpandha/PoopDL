"use client";
import React, { useState } from 'react'
import { cn } from '@/app/__components/Utils'
import { scrapeVideoStep } from '../_utils/api';
import type { ScrapeResponse } from '../_utils/api';
import saveAs from 'file-saver';
import 'video.js/dist/video-js.css';

const FormValidations = {
    url: {
        REGEX: /https?:\/\/(.+?)\/(d|e)\/([a-zA-Z0-9]+)/,
        ERROR_MESSAGE: "Please enter a valid PoopDL URL.",
    },
};
/* eslint-disable @typescript-eslint/no-explicit-any */
/* eslint-disable @typescript-eslint/no-unused-vars */
/* eslint-disable @next/next/no-img-element*/
const PoopDLForm = (props: { className?: string }) => {
    const [errorMessage, setErrorMessage] = useState("");
    const [url, setUrl] = useState("");
    const [step, setStep] = useState(1);
    const [data, setData] = useState<any>({});
    const [loading, setLoading] = useState(false);
    const [log, setLog] = useState<string[]>([]);
    const [isShow, setIsShow] = useState(false);
    const [isVideoView, setIsVideoView] = useState(false);

    const handleScrape = async () => {
        setStep(1);
        setData({});
        setLog([]);
        setLoading(true);

        let currentStep = 1;
        let payload: any = { url };
        let accumulatedData: any = {};

        try {
            while (currentStep < 10) {
                const res: ScrapeResponse = await scrapeVideoStep(currentStep, payload);
                setLog((prev) => [...prev, `${res.message}`]);
                payload = { ...payload, ...res.data };
                accumulatedData = { ...accumulatedData, ...res.data };
                setData(accumulatedData);
                currentStep = res.step + 1;
                if (res.status === "error") break;
                if (!(accumulatedData.video_src === undefined || accumulatedData.video_src === null)) break;
            }
            if (accumulatedData.video_src) {
                setTimeout(() => {
                    setLog((prev) => [...prev, "Scraping completed."]);
                }, 1000);
            }
        } catch (e) {
            setLog((prev) => [...prev, `Error: ${e instanceof Error ? e.message : 'Unknown error'}`]);
            setErrorMessage("An error occurred while scraping the video. Please try again.");
        }

        setLoading(false);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!FormValidations.url.REGEX.test(url)) {
            setErrorMessage(FormValidations.url.ERROR_MESSAGE);
        } else {
            setErrorMessage("");
            console.log("Form submitted with URL:", url);
            handleScrape();
        }
    };

    const handleDownload = () => {
        if (data.video_src) {
            saveAs(data.video_src, `${data.title}.mp4`);
        } else {
            setLog((prev) => [...prev, "No video source available."]);
        }
    };

    const viewVideoHandle = () => {
        setIsVideoView((s) => !s);
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
                        {loading ? "Memproses..." : "Download"}
                    </button>
                </div>
            </form>
            <div className="mt-4">
                {/* <h2 className="text-lg font-semibold mb-1">Log:</h2> */}
                <ul className="text-sm p-2 rounded max-h-60 overflow-y-auto text-left">
                    {log.map((entry, i) => (
                        <li key={i}>• {entry}</li>
                    ))}
                </ul>
            </div>

            {data.video_src && (
                <div className="flex flex-col justify-center">
                    {/* <div
                        className="relative flex flex-col md:flex-row md:space-x-5 space-y-3 md:space-y-0 rounded-xl shadow-lg p-3 max-w-xs md:max-w-3xl mx-auto border border-white bg-white">
                        <div className="w-full md:w-1/3 bg-white grid place-items-center">
                            <img src="https://images.pexels.com/photos/4381392/pexels-photo-4381392.jpeg?auto=compress&cs=tinysrgb&dpr=1&w=500" alt="tailwind logo" className="rounded-xl" />
                        </div>
                        <div className="w-full md:w-2/3 bg-white flex flex-col space-y-2 p-3">
                            <div className="flex justify-between item-center">
                                <p className="text-gray-500 font-medium hidden md:block">Vacations</p>
                                <div className="flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5 text-yellow-500" viewBox="0 0 20 20"
                                        fill="currentColor">
                                        <path
                                            d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                    </svg>
                                    <p className="text-gray-600 font-bold text-sm ml-1">
                                        4.96
                                        <span className="text-gray-500 font-normal">(76 reviews)</span>
                                    </p>
                                </div>
                                <div className="">
                                    <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5 text-pink-500" viewBox="0 0 20 20"
                                        fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div className="bg-gray-200 px-3 py-1 rounded-full text-xs font-medium text-gray-800 hidden md:block">
                                    Superhost</div>
                            </div>
                            <h3 className="font-black text-gray-800 md:text-3xl text-xl">{data.title}</h3>
                            <p className="md:text-lg text-gray-500 text-base">The best kept secret of The Bahamas is the country’s sheer
                                size and diversity. With 16 major islands, The Bahamas is an unmatched destination</p>
                            <p className="text-xl font-black text-gray-800">
                                $110
                                <span className="font-normal text-gray-600 text-base">/night</span>
                            </p>
                        </div>
                    </div> */}
                    <button
                        onClick={() => setIsShow((s) => !s)}
                        type="button"
                        className="py-2.5 px-5 me-2 mb-2 text-sm font-medium text-gray-900 focus:outline-none bg-white rounded-lg border border-gray-200 hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-4 focus:ring-gray-100 dark:focus:ring-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700">
                        {isShow ? "Hide Result" : "Show Result"}
                    </button>

                    {isShow && (
                        <div>
                            <div className="mx-auto flex max-w-2xl flex-col items-start space-x-4 rounded-md border border-gray-300 bg-white p-4 shadow-md">
                                {!isVideoView ? (
                                    <img
                                        src={data.thumbnail}
                                        alt="thumbnail"
                                        className="rounded-sm object-cover max-h-96 mx-auto flex items-center justify-center"
                                    />
                                ) : (
                                    <div className="video-container mx-auto flex items-center justify-center">
                                        <video
                                            id="video-player"
                                            className="video-js vjs-default-skin"
                                            controls
                                            preload="auto"
                                            width="320" 
                                            height="240"
                                            data-setup="{}"
                                        >
                                            <source src={data.video_src} type="video/mp4" />
                                            Your browser does not support the video tag.
                                        </video>
                                    </div>
                                )}
                                <div className="flex flex-1 flex-col">
                                    <h2 className="mt-6 line-clamp-2 text-left font-semibold text-gray-900 sm:text-2xl">{data.title}</h2>
                                    <div className="flex flex-col mt-3">
                                        <div className="flex text-center items-center">
                                            <svg className="h-3 w-3 mx-1.5 flex justify-center items-center" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" strokeWidth="0"></g><g id="SVGRepo_tracerCarrier" strokeLinecap="round" strokeLinejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M12 7V12L14.5 10.5M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="#000000" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"></path> </g>
                                            </svg>
                                            <p className="flex text-sm text-gray-600">Duration: {data.length}</p>
                                        </div>
                                        <div className="flex text-center items-center">
                                            <svg className="h-3 w-3 mx-1.5 flex justify-center items-center" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" strokeWidth="0"></g><g id="SVGRepo_tracerCarrier" strokeLinecap="round" strokeLinejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M12 7V12L14.5 10.5M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="#000000" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"></path> </g>
                                            </svg>
                                            <p className="flex text-sm text-gray-600">Size: {data.size}</p>
                                        </div>
                                        <div className="flex text-center items-center">
                                            <svg className="h-3 w-3 mx-1.5 flex justify-center items-center" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" strokeWidth="0"></g><g id="SVGRepo_tracerCarrier" strokeLinecap="round" strokeLinejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M12 7V12L14.5 10.5M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="#000000" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"></path> </g>
                                            </svg>
                                            <p className="flex text-sm text-gray-600">Upload Date: {data.uploadate}</p>
                                        </div>
                                        <div className="flex text-center items-center">
                                            <svg className="h-3 w-3 mx-1.5 flex justify-center items-center" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" strokeWidth="0"></g><g id="SVGRepo_tracerCarrier" strokeLinecap="round" strokeLinejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M12 7V12L14.5 10.5M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="#000000" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"></path> </g>
                                            </svg>
                                            <p className="flex text-sm text-gray-600">Url: {data.url}</p>
                                        </div>
                                    </div>
                                    <div className="mt-4 items-center gap-3 flex justify-center">
                                        <button onClick={viewVideoHandle} className='bg-purple-700 focus:outline-none focus:ring-4 focus:ring-purple-300 font-semibold hover:bg-purple-800 px-5 py-1.5 rounded text-sm text-white'>View Video</button>
                                        <button onClick={handleDownload} className="rounded bg-green-600 px-4 py-1.5 text-sm font-semibold text-white shadow hover:bg-green-700">Download</button>
                                        <button className="rounded border border-red-600 px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-100">Get Thumbnail</button>
                                    </div>
                                </div>
                            </div>

                        </div>
                    )}

                </div>
            )}
        </div>
    );
};

export default PoopDLForm;