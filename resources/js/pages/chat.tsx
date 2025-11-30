"use client"

import type React from "react"

import { useState, useEffect, useRef } from "react"
import { Button } from "@/components/ui/button"
import { Bot, User, Plus, Globe, MoreHorizontal, Mic, ArrowUp } from "lucide-react"
import AppLayout from "@/layouts/app-layout"
import { usePage } from '@inertiajs/react'
import { SharedData } from '@/types'

interface Message {
  role: "user" | "assistant"
  content: string
  timestamp: Date
}

export default function ChatPage() {
  const { auth } = usePage<SharedData>().props
  const [messages, setMessages] = useState<Message[]>([])
  const [prompt, setPrompt] = useState("")
  const [isLoading, setIsLoading] = useState(false)
  const [sessionId] = useState(() => `chat_${auth.user?.id}_${Date.now()}`)
  const messagesEndRef = useRef<HTMLDivElement>(null)
  const textareaRef = useRef<HTMLTextAreaElement>(null)

  const getCsrfToken = (): string => {
    const name = 'XSRF-TOKEN=';
    const cookies = document.cookie.split(';');
    for (let cookie of cookies) {
      cookie = cookie.trim();
      if (cookie.indexOf(name) === 0) {
        return decodeURIComponent(cookie.substring(name.length));
      }
    }
    return '';
  };

  const scrollToBottom = () => {
    messagesEndRef.current?.scrollIntoView({ behavior: "smooth" })
  }

  useEffect(() => {
    scrollToBottom()
  }, [messages, isLoading])

  // Auto-resize textarea
  const handleTextareaChange = (e: React.ChangeEvent<HTMLTextAreaElement>) => {
    setPrompt(e.target.value)
    if (textareaRef.current) {
      textareaRef.current.style.height = "auto"
      textareaRef.current.style.height = Math.min(textareaRef.current.scrollHeight, 120) + "px"
    }
  }

  const handleSubmit = async () => {
    if (!prompt.trim() || isLoading) return

    const userMessage: Message = {
      role: "user",
      content: prompt,
      timestamp: new Date(),
    }

    setMessages((prev) => [...prev, userMessage])
    setPrompt("")
    setIsLoading(true)

    if (textareaRef.current) {
      textareaRef.current.style.height = "auto"
    }

    try {
      const response = await fetch("/chat", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "Accept": "application/json",
          "X-XSRF-TOKEN": getCsrfToken(),
          "X-Requested-With": "XMLHttpRequest",
        },
        credentials: "same-origin",
        body: JSON.stringify({
          message: prompt,
          session_id: sessionId,
        }),
      })

      const data = await response.json()

      if (data.success) {
        const assistantMessage: Message = {
          role: "assistant",
          content: data.response || "No response received",
          timestamp: new Date(),
        }
        setMessages((prev) => [...prev, assistantMessage])
      } else {
        const errorMessage: Message = {
          role: "assistant",
          content: `Error: ${data.error || "Unknown error occurred"}`,
          timestamp: new Date(),
        }
        setMessages((prev) => [...prev, errorMessage])
      }
    } catch (error) {
      const errorMessage: Message = {
        role: "assistant",
        content: `Error: ${error instanceof Error ? error.message : "Failed to send message"}`,
        timestamp: new Date(),
      }
      setMessages((prev) => [...prev, errorMessage])
    } finally {
      setIsLoading(false)
    }
  }

  const handleKeyPress = (e: React.KeyboardEvent) => {
    if (e.key === "Enter" && !e.shiftKey) {
      e.preventDefault()
      handleSubmit()
    }
  }

  // Auto-send message from URL params
  useEffect(() => {
    const urlParams = new URLSearchParams(window.location.search)
    const messageParam = urlParams.get("message")
    if (messageParam?.trim()) {
      setPrompt(messageParam)
      setTimeout(() => {
        handleSubmit()
      }, 100)
    }
  }, [])

  return (
    <AppLayout>
    <div className="flex h-screen flex-col bg-background">
      {/* Chat Messages Container - Scrollable */}
      <div className="flex-1 overflow-y-auto">
        <div className="max-w-4xl mx-auto w-full px-4 sm:px-6 py-4 sm:py-6">
          {messages.length === 0 ? (
            <div className="flex h-full items-center justify-center text-center">
              <div className="space-y-3">
                <Bot className="mx-auto h-12 w-12 text-muted-foreground" />
                <p className="text-lg font-medium text-foreground">Start chatting with your AI assistant</p>
                <p className="text-sm text-muted-foreground">Try asking: "What can you help me with?"</p>
              </div>
            </div>
          ) : (
            <div className="space-y-4">
              {messages.map((message, index) => (
                <div key={index} className={`flex gap-3 ${message.role === "user" ? "justify-end" : "justify-start"}`}>
                  {message.role === "assistant" && (
                    <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary text-primary-foreground">
                      <Bot className="h-4 w-4" />
                    </div>
                  )}
                  <div
                    className={`max-w-xl rounded-lg px-4 py-2 ${
                      message.role === "user" ? "bg-primary text-primary-foreground" : "bg-muted"
                    }`}
                  >
                    <p className="whitespace-pre-wrap break-words text-sm">{message.content}</p>
                    <p className="mt-1 text-xs opacity-70">{message.timestamp.toLocaleTimeString()}</p>
                  </div>
                  {message.role === "user" && (
                    <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-muted">
                      <User className="h-4 w-4" />
                    </div>
                  )}
                </div>
              ))}
              {isLoading && (
                <div className="flex gap-3 justify-start">
                  <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary text-primary-foreground">
                    <Bot className="h-4 w-4" />
                  </div>
                  <div className="rounded-lg bg-muted px-4 py-2">
                    <div className="flex gap-1">
                      <span className="h-2 w-2 animate-bounce rounded-full bg-foreground [animation-delay:-0.3s]"></span>
                      <span className="h-2 w-2 animate-bounce rounded-full bg-foreground [animation-delay:-0.15s]"></span>
                      <span className="h-2 w-2 animate-bounce rounded-full bg-foreground"></span>
                    </div>
                  </div>
                </div>
              )}
              <div ref={messagesEndRef} />
            </div>
          )}
        </div>
      </div>

      {/* Input Area */}
      <div className="bg-background p-4 sm:p-6">
        <div className="max-w-4xl mx-auto w-full">
          {/* Prompt Input */}
          <div className="relative w-full rounded-3xl border-2 border-border bg-card p-0 pt-1 shadow-sm">
            <div className="flex flex-col">
              {/* Textarea */}
              <textarea
                ref={textareaRef}
                value={prompt}
                onChange={handleTextareaChange}
                onKeyPress={handleKeyPress}
                placeholder="Ask anything..."
                disabled={isLoading}
                rows={1}
                className="min-h-[44px] max-h-[120px] resize-none overflow-hidden pt-3 pl-4 text-base leading-[1.3] bg-transparent border-none outline-none placeholder-muted-foreground disabled:opacity-50 sm:text-base md:text-base"
              />

              {/* Actions */}
              <div className="mt-5 flex w-full items-center justify-between gap-2 px-3 pb-3">
                <div className="flex items-center gap-2">
                   
                </div>

                <div className="flex items-center gap-2">
                  
                  <Button
                    size="icon"
                    disabled={!prompt.trim() || isLoading}
                    onClick={handleSubmit}
                    className="size-9 rounded-full"
                    title="Send message"
                  >
                    {!isLoading ? <ArrowUp size={18} /> : <span className="size-2 rounded-xs bg-white animate-spin" />}
                  </Button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    </AppLayout>
  )
}
