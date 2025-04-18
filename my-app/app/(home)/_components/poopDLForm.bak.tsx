"use client"
import React from 'react'
import { cn } from "../../__components/Utils"
import { Form, FormField, FormItem, FormLabel, FormControl } from "./formUi"
import { useForm } from "react-hook-form";
import { z } from "zod";
import { zodResolver } from "@hookform/resolvers/zod";
import { Input } from "./input"
import { Button } from "./button"
import { Download, Loader2, X } from "lucide-react";

const FormValidations = {
    url: {
        REGEX: /^(https?:\/\/)?(www\.)?instagram\.com\/(p|reel)\/.+$/,
    },
};

export function isShortcodePresent(url: string) {
    const regex = /\/(p|reel)\/([a-zA-Z0-9_-]+)\/?/;
    const match = url.match(regex);

    if (match && match[2]) {
        return true;
    }

    return false;
}

export function getPostShortcode(url: string): string | null {
    const regex = /\/(p|reel)\/([a-zA-Z0-9_-]+)\/?/;
    const match = url.match(regex);

    if (match && match[2]) {
        const shortcode = match[2];
        return shortcode;
    } else {
        return null;
    }
}

const useFormSchema = () => {

    return z.object({
        url: z
            .string({ required_error: "PoopHD post URL is required" })
            .trim()
            .min(1, {
                message: "PoopHD post URL is required",
            })
            .regex(FormValidations.url.REGEX, "Please enter a valid PoopHD URL")
            .refine(
                (value) => {
                    return isShortcodePresent(value);
                },
                { message: "Please enter a valid PoopHD URL" }
            ),
    });
};

const poopDLForm = (props: { className?: string }) => {
    const inputRef = React.useRef<HTMLInputElement>(null);
    const formSchema = useFormSchema();

    const form = useForm<z.infer<typeof formSchema>>({
        resolver: zodResolver(formSchema),
        defaultValues: {
            url: "",
        },
    });

    const isShowClearButton = form.watch("url").length > 0;

    function clearUrlField() {
        form.setValue("url", "");
        form.clearErrors("url");
        inputRef.current?.focus();
    }

    //   const {
    //     isError,
    //     isPending,
    //     mutateAsync: getInstagramPost,
    //   } = useGetInstagramPostMutation();

    const isPending = false; //isPending;
    const isError = false; //isError;

    const isDisabled = isPending || !form.formState.isDirty;

    async function onSubmit(values: z.infer<typeof formSchema>) {
        if (isError) {
            toast.dismiss("toast-error");
        }

        const shortcode = getPostShortcode(values.url);

        if (!shortcode) {
            form.setError("url", { message: t("inputs.url.validation.invalid") });
            return;
        }

        const cachedUrl = getCachedUrl(shortcode);
        if (cachedUrl?.invalid) {
            form.setError("url", { message: t(cachedUrl.invalid.messageKey) });
            return;
        }

        if (cachedUrl?.videoUrl) {
            triggerDownload(cachedUrl.videoUrl);
            return;
        }

        try {
            const { data, status } = await getInstagramPost({ shortcode });

            if (status === HTTP_CODE_ENUM.OK) {
                const downloadUrl = data.data.xdt_shortcode_media.video_url;
                if (downloadUrl) {
                    triggerDownload(downloadUrl);
                    setCachedUrl(shortcode, downloadUrl);
                    toast.success(t("toasts.success"), {
                        id: "toast-success",
                        position: "top-center",
                        duration: 1500,
                    });
                } else {
                    throw new Error("Video URL not found");
                }
            } else if (
                status === HTTP_CODE_ENUM.NOT_FOUND ||
                status === HTTP_CODE_ENUM.BAD_REQUEST ||
                status === HTTP_CODE_ENUM.TOO_MANY_REQUESTS ||
                status === HTTP_CODE_ENUM.INTERNAL_SERVER_ERROR
            ) {
                const errorMessageKey = `serverErrors.${data.error}`;
                form.setError("url", { message: t(errorMessageKey) });
                if (
                    status === HTTP_CODE_ENUM.BAD_REQUEST ||
                    status === HTTP_CODE_ENUM.NOT_FOUND
                ) {
                    setCachedUrl(shortcode, undefined, {
                        messageKey: errorMessageKey,
                    });
                }
            } else {
                throw new Error("Failed to fetch video");
            }
        } catch (error) {
            console.error(error);
            toast.error(t("toasts.error"), {
                dismissible: true,
                id: "toast-error",
                position: "top-center",
            });
        }
    }

    // const errorMessage = form.formState.errors.url?.message;
    const errorMessage = null;

    React.useEffect(() => {
        inputRef.current?.focus();
    }, []);

    return (
        <div className={cn("w-full space-y-2", props.className)}>
            {errorMessage ? (
                <p className="h-4 text-sm text-red-500 sm:text-start">{errorMessage}</p>
            ) : (
                <div className="h-4"></div>
            )}
            <Form {...form}>
                <form
                    onSubmit={form.handleSubmit(onSubmit)}
                    className="flex w-full flex-col gap-2 sm:flex-row sm:items-end"
                >
                    <FormField
                        control={form.control}
                        name="url"
                        rules={{ required: true }}
                        render={({ field }) => (
                            <FormItem className="w-full">
                                <FormLabel className="sr-only">
                                "Video URL"
                                </FormLabel>
                                <FormControl>
                                    <div className="relative w-full">
                                        <Input
                                            {...field}
                                            type="url"
                                            ref={inputRef}
                                            minLength={1}
                                            maxLength={255}
                                            placeholder={"Paste PoopHD video URL here..."}
                                        />
                                        {isShowClearButton && (
                                            <Button
                                                size="icon"
                                                variant="ghost"
                                                onClick={clearUrlField}
                                                className="absolute top-1/2 right-2 h-4 w-4 -translate-y-1/2 cursor-pointer"
                                            >
                                                <X className="text-red-500" />
                                            </Button>
                                        )}
                                    </div>
                                </FormControl>
                            </FormItem>
                        )}
                    />
                    <Button
                        disabled={isDisabled}
                        type="submit"
                        className="bg-teal-500 text-white hover:bg-teal-600 dark:bg-teal-700 dark:hover:bg-teal-600"
                    >
                        {isPending ? (
                            <Loader2 className="h-4 w-4 animate-spin" />
                        ) : (
                            <Download className="h-4 w-4" />
                        )}
                        {"Download"}
                    </Button>
                </form>
            </Form>
            <p className="text-muted-foreground text-center text-xs">{"Works with PoopHD posts and reels"}</p>
        </div>
    )
}

export default poopDLForm