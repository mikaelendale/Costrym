# LarAgent: A Beginner's Guide to Building AI Agents in Laravel

## Introduction to LarAgent

LarAgent is a powerful and intuitive framework designed to seamlessly integrate Artificial Intelligence (AI) agents into your Laravel applications. It simplifies the complex process of building, extending, and managing AI agents by leveraging Laravel's familiar syntax and design patterns. Imagine being able to create sophisticated AI functionalities within your existing Laravel projects with the same ease as defining an Eloquent model. That's precisely what LarAgent aims to achieve.

At its core, LarAgent provides a structured approach to developing AI agents that can understand natural language, perform actions, and interact with various external systems. This means you can empower your applications with intelligent capabilities, ranging from automated customer support to complex data processing and integration with third-party services.

### Why LarAgent?

LarAgent stands out by offering a developer-friendly experience for AI integration. It abstracts away much of the underlying complexity of interacting with large language models (LLMs) and tool orchestration. By adhering to Laravel's conventions, it allows developers to quickly grasp and implement AI features without a steep learning curve. This framework is particularly beneficial for Laravel developers looking to add AI capabilities to their applications efficiently and effectively.

## Core Concepts: Understanding the Building Blocks

To effectively utilize LarAgent, it's crucial to understand its fundamental components: Agents and Tools. These two concepts work in tandem to define the behavior and capabilities of your AI.

### Agents: The Brains of Your AI

In LarAgent, an **Agent** is the central entity that encapsulates the intelligence and behavior of your AI assistant. Think of an Agent as the 


mind of your AI, defining how it thinks, what it knows, and how it interacts with the world. Each agent is designed to perform specific tasks or fulfill particular roles within your application.

#### How Agents Work

LarAgent agents are built upon the `LarAgent\Agent` class. When you create an agent, you are essentially defining a set of instructions, capabilities, and configurations that dictate its behavior. These configurations include which Large Language Model (LLM) the agent will use (e.g., OpenAI's GPT models, Google's Gemini, Groq), how it manages conversation history, and what external tools it has access to.

#### Creating Your First Agent

For beginners, the easiest way to create an agent is by using LarAgent's Artisan command. If you're familiar with Laravel, Artisan commands are a common way to generate boilerplate code and perform various tasks. LarAgent provides a similar command to scaffold a new agent:

```php
php artisan make:agent YourAgentName
```

This command will generate a new PHP class in your `App\AiAgents` directory (or a similar namespace you configure). This class will extend `LarAgent\Agent` and come with a basic structure, ready for you to customize. Let's look at a simplified example of what this generated agent class might look like:

```php
namespace App\AiAgents;

use LarAgent\Agent;

class YourAgentName extends Agent
{
    protected $model = 'gpt-4o-mini'; // The default LLM model this agent will use
    protected $history = 'in_memory'; // How the agent will store chat history
    protected $provider = 'default'; // Which LLM provider configuration to use
    protected $tools = []; // A list of tools this agent can use

    public function instructions()
    {
        return "Define your agent's instructions here.";
    }

    public function prompt($message)
    {
        return $message;
    }
}
```

**Explanation for Beginners:**

*   `protected $model`: This is where you specify which specific AI model your agent will use. `gpt-4o-mini` is a common choice, but you can switch to others like `gemini` or `gpt-4o` depending on your needs and the provider you're using.
*   `protected $history`: This defines how your agent remembers past conversations. `'in_memory'` means it only remembers for the current session, but you can configure it to use your application's cache, database sessions, or files for more persistent memory.
*   `protected $provider`: LarAgent allows you to configure multiple LLM providers (like OpenAI, Google Gemini, Groq). This property tells your agent which of those configured providers to use.
*   `protected $tools`: This is an empty array by default, but it's where you'll list all the 


external functionalities (tools) your agent can access and utilize. We will delve deeper into tools in the next section.
*   `instructions()` method: This is a crucial method where you define the **system instructions** for your agent. These instructions tell the AI what its role is, what kind of responses it should provide, and any specific rules it needs to follow. For example, you might instruct it to be a helpful customer support bot or a creative story generator.
*   `prompt($message)` method: This method handles how the user's input (`$message`) is processed before being sent to the LLM. For simple cases, it might just return the message as is, but for more advanced scenarios, you could modify or enhance the prompt here.

#### Configuring an Agent: Tailoring Behavior

LarAgent provides extensive configuration options to fine-tune your agent's behavior. These configurations can be set as properties within your agent class or dynamically at runtime using chainable methods. Understanding these properties is key to building effective and specialized AI agents.

Here are some of the most important configuration properties:

*   **`$instructions` (string):** This property defines the agent's core behavior and role. It's the primary way to tell your AI what its purpose is. You can set it directly as a string or use the `instructions()` method for more dynamic instructions.

    ```php
    // Example of setting instructions as a property
    protected $instructions = "You are a helpful assistant specialized in weather forecasting.";
    
    // Example of setting instructions using the method (more dynamic)
    public function instructions()
    {
        return "You are a friendly chatbot that helps users find recipes based on ingredients they have.";
    }
    ```

*   **`$history` (string or class):** This property determines how your agent maintains conversation history. LarAgent offers several built-in options:
    *   `'in_memory'`: History is only kept for the current request/session and is lost afterward. Suitable for stateless interactions.
    *   `'session'`: History is stored in the Laravel session, persisting across multiple requests within a user's session.
    *   `'cache'`: History is stored in Laravel's cache, offering more flexibility in terms of persistence and sharing.
    *   `'file'`: History is stored in a file, useful for debugging or specific persistence needs.
    *   `'json'`: History is stored as a JSON string, often used for custom storage solutions.

    ```php
    // Example: Using cache for chat history
    protected $history = 'cache';
    
    // Or using the class directly
    protected $history = \LarAgent\History\CacheChatHistory::class;
    ```

*   **`$driver` (string):** This specifies the underlying driver class that handles communication with the AI provider (e.g., OpenAI, Gemini). You typically won't change this unless you're implementing a custom LLM integration.

    ```php
    // Example: Using the OpenAI driver
    protected $driver = \LarAgent\Drivers\OpenAi\OpenAiDriver::class;
    ```

*   **`$provider` (string):** This references a specific provider configuration defined in your `config/laragent.php` file. You can set up different API keys and settings for various LLM providers (e.g., a 'default' OpenAI provider, a 'gemini' provider, a 'groq' provider).

    ```php
    // Example: Using a custom 'openai-gpt4' provider configuration
    protected $provider = 'openai-gpt4';
    ```

*   **`$model` (string):** This property allows you to select the specific language model to use from your chosen provider. Different models have different capabilities and cost implications.

    ```php
    // Example: Using a more powerful model
    protected $model = 'gpt-4o';
    ```

*   **`$maxCompletionTokens` (int):** This limits the maximum number of tokens (words or pieces of words) the AI's response can contain. It's useful for controlling response length and managing costs.

    ```php
    // Example: Limiting response to 500 tokens
    protected $maxCompletionTokens = 500;
    ```

*   **`$temperature` (float):** This controls the creativity and randomness of the AI's responses. A value of `0.0` makes the responses very focused and deterministic, while a higher value (e.g., `2.0`) makes them more creative and varied. A common value for balanced responses is `0.7`.

    ```php
    // Example: For balanced creativity
    protected $temperature = 0.7;
    ```

*   **`$n` (int):** This specifies how many chat completion choices to generate for each input message. Be cautious with this, as you will be charged for all generated tokens across all choices. For most applications, keeping `$n` as `1` is sufficient and cost-effective.

*   **`$topP` (float):** An alternative to `$temperature`, this controls the diversity of responses by considering only tokens within a certain probability mass. For example, `0.1` means only tokens comprising the top 10% probability mass are considered. It's generally recommended to adjust either `$temperature` or `$topP`, but not both simultaneously.

*   **`$frequencyPenalty` (float):** A number between `-2.0` and `2.0`. Positive values penalize new tokens based on their existing frequency in the text, reducing the likelihood of the model repeating the same phrases verbatim.

*   **`$presencePenalty` (float):** A number between `-2.0` and `2.0`. Positive values penalize new tokens based on whether they appear in the text so far, encouraging the model to introduce new topics.

#### Using an Agent: Interacting with Your AI

Once an agent is configured, you can interact with it in two primary ways: direct response or using chainable methods for more granular control.

**1. Direct Response:**

This is the simplest way to get an immediate response from your agent. You use the `for()` method to specify a chat history name (which helps the agent maintain context for that specific conversation) and then call `respond()` with your message.

```php
use App\AiAgents\WeatherAgent;

// Using a specific chat history name (e.g., 'user_123_chat')
echo WeatherAgent::for('user_123_chat')->respond('What is the weather like in London?');
// Expected output: "The weather in London is currently..."
```

**2. Using Chainable Methods:**

For more advanced scenarios where you need to dynamically adjust agent settings for a specific interaction (e.g., changing the temperature for a single query), you can use chainable methods. This provides greater flexibility.

```php
use App\AiAgents\WeatherAgent;

$response = WeatherAgent::for('user_123_chat')
    ->message('What is the weather like in Paris?') // Set the message for this interaction
    ->temperature(0.9) // Optional: Override the default temperature for this specific call
    ->respond(); // Get the response

echo $response;
```

**Image Input:**

LarAgent also supports passing publicly accessible image URLs to your agent as input. This is particularly useful for agents that need to perform visual analysis or respond based on image content.

```php
use App\AiAgents\ImageAnalysisAgent;
use LarAgent\Messages\Message;

$imageUrl = 'https://example.com/path/to/your/image.jpg';

// Create a UserMessage instance with the image URL
$userMessage = Message::user('Analyze this image:', ['image_url' => $imageUrl]);

$response = ImageAnalysisAgent::for('image_session_1')->message($userMessage)->respond();

echo $response; // The agent's analysis of the image
```

### Use Case Block: Agent - Customer Support Chatbot

```markdown
**Use Case:** Building an Intelligent Customer Support Chatbot

**Problem:** Many businesses struggle with providing instant and accurate customer support, leading to long wait times and frustrated customers. Manual support is resource-intensive.

**Solution with LarAgent:** Develop a LarAgent-powered chatbot that can handle common customer inquiries, provide product information, and guide users through troubleshooting steps.

**Agent Configuration Example:**

```php
namespace App\AiAgents;

use LarAgent\Agent;
use LarAgent\History\CacheChatHistory;

class SupportChatbotAgent extends Agent
{
    protected $model = 'gpt-4o-mini'; // Or a more powerful model like 'gpt-4o' for complex queries
    protected $history = CacheChatHistory::class; // Persist history using cache
    protected $provider = 'default'; // Assuming 'default' is configured for OpenAI
    protected $temperature = 0.5; // Keep responses focused and less creative

    public function instructions()
    {
        return "You are a helpful and polite customer support assistant for 'Acme Inc.'. Your goal is to answer user questions about our products and services, provide troubleshooting steps, and direct users to human support if you cannot resolve their issue. Always maintain a positive and professional tone.";
    }

    protected $tools = [
        // Tools for fetching product details, checking order status, etc.
        \App\Tools\ProductInfoTool::class,
        \App\Tools\OrderStatusTool::class,
        \App\Tools\TroubleshootingGuideTool::class,
    ];
}
```

**How it Works:**
1.  A customer initiates a chat on the website.
2.  The `SupportChatbotAgent` receives the customer's message.
3.  Based on its `instructions`, the agent attempts to understand the query.
4.  If the query requires external information (e.g., "What is the price of product X?" or "Where is my order?"), the agent utilizes its configured `tools` (like `ProductInfoTool` or `OrderStatusTool`) to fetch the necessary data.
5.  The agent then formulates a clear and concise response, adhering to its defined tone and instructions.
6.  If the agent cannot resolve the issue, it can be instructed to suggest contacting a human agent.

**Benefits:**
*   **24/7 Availability:** Provides instant support around the clock.
*   **Reduced Workload:** Frees up human support agents to focus on more complex issues.
*   **Consistent Responses:** Ensures uniform and accurate information delivery.
*   **Scalability:** Easily handles a large volume of inquiries without additional human resources.
```

### Tools: Extending Agent Capabilities

While agents are the brains, **Tools** are the hands and feet of your AI. Tools (also known as function calling) allow your AI agents to interact with the outside world beyond just generating text. This means your agent can perform actions like fetching real-time data from APIs, sending emails, interacting with databases, or even controlling other parts of your application. Without tools, an AI agent is limited to its training data; with tools, it can become a dynamic and interactive component of your system.

#### How Tools Work

LarAgent integrates with the function calling capabilities of modern LLMs. When an agent determines that it needs external information or needs to perform an action to fulfill a user's request, it can 


call a 


pre-defined tool. The LLM decides *when* to call a tool and *what arguments* to pass to it, based on the conversation context and the tool's description. LarAgent then executes the corresponding PHP function or class method, and the result is fed back to the LLM, allowing it to generate a more informed response.

#### Tool Configuration

Tools in LarAgent are configured within your Agent class using two key properties:

*   **`protected $parallelToolCalls;` (boolean):** This property controls whether your agent can execute multiple tools simultaneously. Some advanced LLMs support this, allowing for more efficient processing of complex requests that require multiple external actions. If your chosen LLM doesn't support parallel tool calls, you can set this to `null` to remove it from the request.

    ```php
    // Enable parallel tool calls if supported by the LLM
    protected $parallelToolCalls = true;
    
    // Disable parallel tool calls (or set to null if not supported by model)
    // protected $parallelToolCalls = false;
    ```

*   **`protected $tools = [];` (array):** This array is where you list the tool classes or methods that your agent can utilize. Each entry in this array represents a capability your agent possesses.

    ```php
    // Example: Listing tool classes
    protected $tools = [
        \App\Tools\WeatherTool::class,
        \App\Tools\CalculatorTool::class,
    ];
    ```

#### Creating and Registering Tools

LarAgent offers three flexible ways to define and register tools, catering to different levels of complexity and reusability:

**1. Using the `#[Tool]` Attribute (Recommended for Simplicity):**

This is the most straightforward method for creating tools, especially for functions directly related to your agent's class. You simply add the `#[Tool]` attribute above a public method within your agent class. The attribute takes a description of what the tool does, and LarAgent automatically extracts the method's parameters to inform the LLM about what arguments the tool expects.

```php
namespace App\AiAgents;

use LarAgent\Agent;
use LarAgent\Attributes\Tool;

class WeatherAgent extends Agent
{
    // ... other agent properties

    #[Tool(
        // This is the description the LLM sees. It should be clear and concise.
        // The LLM uses this to decide when to call the tool.
        'Get the current weather in a given location'
    )]
    public function getCurrentWeather($location, $unit = 'celsius')
    {
        // In a real application, you would call an external weather API here.
        // For demonstration, we return a static string.
        return "The weather in {$location} is 22 degrees {$unit}.";
    }
}
```

**Explanation for Beginners:**

*   `#[Tool('...')]:` This special line (called an 


attribute) tells LarAgent that the `getCurrentWeather` method is a tool that your AI agent can use. The text inside the parentheses is a human-readable description of what the tool does. The LLM reads this description and decides if and when to call this tool.
*   `$location, $unit = 'celsius'`: These are the parameters (inputs) the tool expects. The LLM will try to extract these values from the user's query and pass them to your tool. For example, if a user asks "What's the weather in New York in Fahrenheit?", the LLM would identify `New York` as the `$location` and `Fahrenheit` as the `$unit`.

**Tool with Parameter Descriptions and Enums:**

For even better control and clarity, you can provide detailed descriptions for each parameter and use PHP Enums to restrict the possible values for certain parameters. This helps the LLM understand exactly what kind of input it should provide.

First, define a PHP Enum (a special class that represents a fixed set of named values):

```php
// app/Enums/Unit.php
namespace App\Enums;

enum Unit: string
{
    case CELSIUS = 'celsius';
    case FAHRENHEIT = 'fahrenheit';
}
```

Then, use this Enum in your tool method and provide parameter descriptions:

```php
// app/AiAgents/WeatherAgent.php
use LarAgent\Attributes\Tool;
use App\Enums\Unit;

class WeatherAgent extends Agent
{
    // ... other properties

    #[Tool(
        'Get the current weather in a given location',
        [
            'unit' => 'Unit of temperature (celsius or fahrenheit)',
            'location' => 'The city and state, e.g. San Francisco, CA'
        ]
    )]
    public static function weatherTool(Unit $unit, $location)
    {
        // In a real application, you would use a weather service here.
        return "The weather in {$location} is 25 degrees {$unit->value}.";
    }
}
```

**Explanation for Beginners:**

*   `enum Unit: string`: This defines a new type called `Unit` that can only be `celsius` or `fahrenheit`. This is incredibly useful because it tells the AI exactly what values are acceptable for the `unit` parameter, preventing it from sending invalid inputs.
*   `[
            'unit' => 'Unit of temperature (celsius or fahrenheit)',
            'location' => 'The city and state, e.g. San Francisco, CA'
        ]`: This array provides more detailed, human-readable descriptions for each parameter. The LLM uses these descriptions to better understand how to fill in the parameters when calling the tool.
*   `public static function weatherTool(...)`: It's a good practice to make tool methods `static` if they don't need to access any properties or methods of the agent instance (`$this`).

**2. Programmatic Tool Creation using `LarAgent\Tool` (For Dynamic Tools):**

This method is more flexible and allows you to create tools programmatically, which is useful when your tools need to be generated dynamically or have complex logic for their callbacks. You use the `LarAgent\Tool` class to define the tool's name, description, properties (parameters), and the callback function that will be executed when the tool is called.

```php
use LarAgent\Tool;

class MyAgent extends Agent
{
    // ...

    public function registerTools()
    {
        $user = auth()->user(); // Example: Get the current authenticated user

        return [
            Tool::create("get_user_location", "Returns the current user's city")
                 ->setCallback(function () use ($user) {
                      return $user->location()->city; // Assuming user has a location relationship
                 }),

            Tool::create("get_current_time", "Returns the current time")
                 ->addProperty("timezone", "string", "The timezone, e.g., 'America/New_York'")
                 ->setCallback(function ($timezone) {
                     return now()->timezone($timezone)->toTimeString();
                 }),
        ];
    }
}
```

**Explanation for Beginners:**

*   `Tool::create("tool_name", "Tool description")`: This is how you start defining a new tool programmatically. You give it a unique name and a clear description.
*   `->setCallback(function () { ... })`: This sets the actual PHP code that will run when the tool is called. It can be a simple anonymous function (a `closure`), a reference to a class method, or any other PHP callable.
*   `->addProperty("parameter_name", "type", "description")`: If your tool needs inputs, you define them using `addProperty`. You specify the parameter's name, its expected type (e.g., `string`, `integer`), and a description for the LLM.

**3. Dedicated Tool Classes (For Complex and Reusable Tools):**

For highly complex tools or functionalities that you want to reuse across multiple agents, it's best practice to create dedicated tool classes. These classes extend `LarAgent\Tool` and provide a more structured way to define your tool, its properties, and its execution logic.

```php
// app/Tools/WeatherTool.php
namespace App\Tools;

use LarAgent\Tool;

class WeatherTool extends Tool
{
    protected string $name = 'get_current_weather';
    protected string $description = 'Get the current weather in a given location';

    protected array $properties = [
        'location' => [
            'type' => 'string',
            'description' => 'The city and state, e.g. San Francisco, CA',
        ],
        'unit' => [
            'type' => 'string',
            'description' => 'The unit of temperature',
            'enum' => ['celsius', 'fahrenheit'], // Restrict values using enum
        ],
    ];

    protected array $required = ['location']; // Specify which properties are mandatory

    public function execute(array $input): mixed
    {
        // This method contains the actual logic of your tool.
        // It receives an array of inputs (parameters) from the LLM.
        $location = $input['location'];
        $unit = $input['unit'] ?? 'celsius'; // Default to celsius if not provided

        // In a real scenario, you would call an external API here.
        return "The weather in {$location} is " . rand(10, 30) . " degrees {$unit}.";
    }
}
```

To register this dedicated tool class with your agent, you simply add it to the `$tools` array in your agent class:

```php
// app/AiAgents/MyAgent.php
namespace App\AiAgents;

use LarAgent\Agent;
use App\Tools\WeatherTool; // Import your dedicated tool class

class MyAgent extends Agent
{
    // ...
    protected $tools = [
        WeatherTool::class, // Register the dedicated tool class
        // Other tool classes...
    ];
}
```

**Explanation for Beginners:**

*   `protected string $name`: A unique identifier for your tool.
*   `protected string $description`: A clear description for the LLM.
*   `protected array $properties`: Defines the parameters the tool accepts, including their types and descriptions. The `enum` key is used here to specify allowed values, similar to how PHP Enums work with the `#[Tool]` attribute.
*   `protected array $required`: Lists the names of parameters that *must* be provided by the LLM for the tool to execute.
*   `public function execute(array $input): mixed`: This is the core method where your tool's logic resides. It receives the parameters from the LLM as an associative array (`$input`).

#### Tool Choice: Guiding the Agent

LarAgent allows you to control how your agent uses tools for specific interactions. This is known as "tool choice" and can be very useful for guiding the AI's behavior.

*   **`toolNone()`: Disable Tools:**
    This tells the agent *not* to use any tools for the current response. Useful when you know the query can be answered purely from the LLM's internal knowledge.

    ```php
    // The agent will not use any tools for this question.
    WeatherAgent::for('test_chat')->toolNone()->respond('What is my name?');
    ```

*   **`toolRequired()`: Require at Least One Tool:**
    This forces the agent to use at least one tool to answer the query. If the LLM cannot find a suitable tool, it might indicate that it needs more information or cannot fulfill the request.

    ```php
    // The agent will attempt to use a tool to answer this question.
    WeatherAgent::for('test_chat')->toolRequired()->respond('Who is the current president of the USA?');
    ```

*   **`forceTool('tool_name')`: Force a Specific Tool:**
    This is the most restrictive option, compelling the agent to use a particular tool. This is useful for specific workflows where you know exactly which tool should be invoked.

    ```php
    // Forces the agent to use the 'weatherTool' for this query.
    WeatherAgent::for('test_chat')->forceTool('weatherTool')->respond('What is the weather in New York?');
    ```

**Important Note for Beginners:** `toolRequired()` and `forceTool()` are typically set only for the *first* call in a sequence. After that, LarAgent automatically switches to an 'auto' mode to prevent infinite loops, allowing the LLM to decide naturally whether to use tools.

#### Phantom Tools: Tools Without Local Execution

**Phantom Tools** are a powerful advanced feature in LarAgent. Unlike regular tools that execute a PHP function or method directly within your Laravel application, Phantom Tools do *not* execute locally. Instead, when a Phantom Tool is "called" by the LLM, LarAgent returns a `ToolCallMessage` instance. This message contains all the information about the tool call (its name, parameters, etc.), but it's up to *your application* to decide what to do with this information. This makes them incredibly flexible for scenarios where:

*   You need to integrate with external services dynamically (e.g., calling a third-party API that's not directly integrated into your Laravel app).
*   You want to handle tool execution outside of LarAgent (e.g., sending the tool call information to a separate microservice or a frontend application).
*   You need to expose tool registration/execution via an API, allowing other systems to trigger tool actions.

```php
use LarAgent\PhantomTool;

// Inside your agent or a service provider
$phantomTool = PhantomTool::create(
    'external_payment_processor',
    'Process a payment using an external payment gateway'
)
->addProperty('amount', 'float', 'The amount to charge')
->addProperty('currency', 'string', 'The currency code, e.g., USD')
->setRequired(['amount', 'currency'])
->setCallback('handleExternalPayment'); // This callback won't be executed by LarAgent

// Register the Phantom Tool with your agent
$agent->withTool($phantomTool);

// When the LLM calls 'external_payment_processor', LarAgent returns a ToolCallMessage.
// Your application would then listen for this message and handle the payment externally.
```

**Explanation for Beginners:** Think of a Phantom Tool as a way for your AI to *suggest* an action that needs to happen elsewhere. The AI says, "I need to process a payment of $50 in USD," and instead of LarAgent actually processing it, it just tells your application, "Hey, the AI wants to process a payment of $50 in USD. Here are the details." Your application then takes those details and handles the payment through your existing payment gateway integration.

#### Dynamic Tool Management

LarAgent allows you to add or remove tools from an agent dynamically at runtime. This is useful for scenarios where an agent's capabilities might change based on user permissions, context, or other application logic.

*   **Add tool with instance:**

    ```php
    use LarAgent\Tool;

    $tool = Tool::create('new_feature_tool', 'Activates a new feature for the user')
                ->setCallback(fn () => 'Feature activated!');
    $agent->withTool($tool); // Add the tool to the agent
    ```

*   **Add tool with predefined tool class:**

    ```php
    use App\Tools\AdminOnlyTool;

    // Assuming AdminOnlyTool is a dedicated tool class
    $agent->withTool(AdminOnlyTool::class); // Add the tool by its class name
    ```

*   **Remove tool by name:**

    ```php
    $agent->removeTool('old_feature_tool'); // Remove a tool by its registered name
    ```

*   **Remove tool by instance:**

    ```php
    $toolToRemove = Tool::create('temporary_tool', 'Performs a temporary action');
    $agent->withTool($toolToRemove);
    // ... later, when no longer needed
    $agent->removeTool($toolToRemove);
    ```

*   **Remove tool by class name:**

    ```php
    use App\Tools\DeprecatedTool;

    $agent->removeTool(DeprecatedTool::class); // Remove a tool by its class name
    ```

#### Best Practices for Tools

To ensure your LarAgent tools are effective, reliable, and easy to maintain, follow these best practices:

*   **Do create separate tool classes for complex functionality:** If a tool involves significant logic, external API calls, or might be reused, encapsulate it in its own dedicated tool class (Method 3 above). This promotes code organization and reusability.
*   **Do provide clear, descriptive names and parameter descriptions:** The LLM relies heavily on these descriptions to understand when and how to use your tools. Be as precise and unambiguous as possible.
*   **Do use Enums when you need to restrict the AI to specific options:** Enums are excellent for guiding the LLM to provide valid inputs, reducing errors and improving the reliability of your tool calls.
*   **Don’t create tools with ambiguous functionality or unclear parameter requirements:** If the LLM can't understand what your tool does or what inputs it needs, it won't use it correctly, leading to unexpected behavior.
*   **Don’t expose sensitive operations without proper validation and security checks:** Always assume that inputs coming from the LLM (via tool calls) could be malicious. Implement robust validation and authorization checks within your tool's `execute` method or callback before performing any sensitive operations.

### Use Case Block: Tool - E-commerce Product Search

```markdown
**Use Case:** Enabling an E-commerce Chatbot to Search Products

**Problem:** A customer asks a chatbot, "Do you have any blue t-shirts in stock?" Without tools, the chatbot can only respond based on its general knowledge, which might not include real-time inventory.

**Solution with LarAgent:** Create a `ProductSearchTool` that allows the AI agent to query the e-commerce platform's product database.

**Tool Definition Example (Dedicated Tool Class):**

```php
// app/Tools/ProductSearchTool.php
namespace App\Tools;

use LarAgent\Tool;
use App\Models\Product; // Assuming you have an Eloquent Product model

class ProductSearchTool extends Tool
{
    protected string $name = 'search_products';
    protected string $description = 'Searches the product catalog for items matching given criteria.';

    protected array $properties = [
        'query' => [
            'type' => 'string',
            'description' => 'The search term or product name (e.g., "blue t-shirt", "laptop")',
            'required' => true,
        ],
        'color' => [
            'type' => 'string',
            'description' => 'Optional: Filter products by color',
        ],
        'category' => [
            'type' => 'string',
            'description' => 'Optional: Filter products by category (e.g., "electronics", "apparel")',
        ],
        'min_price' => [
            'type' => 'number',
            'description' => 'Optional: Minimum price for products',
        ],
        'max_price' => [
            'type' => 'number',
            'description' => 'Optional: Maximum price for products',
        ],
    ];

    public function execute(array $input): mixed
    {
        $query = $input['query'];
        $color = $input['color'] ?? null;
        $category = $input['category'] ?? null;
        $minPrice = $input['min_price'] ?? null;
        $maxPrice = $input['max_price'] ?? null;

        $products = Product::where('name', 'like', '%' . $query . '%');

        if ($color) {
            $products->where('color', $color);
        }
        if ($category) {
            $products->where('category', $category);
        }
        if ($minPrice) {
            $products->where('price', '>=', $minPrice);
        }
        if ($maxPrice) {
            $products->where('price', '<=', $maxPrice);
        }

        $results = $products->limit(5)->get(); // Limit to 5 results for brevity

        if ($results->isEmpty()) {
            return 'No products found matching your criteria.';
        } else {
            $output = "Found the following products:\n";
            foreach ($results as $product) {
                $output .= "- {$product->name} (Color: {$product->color}, Price: ".$product->price.")\n";
            }
            return $output;
        }
    }
}
```

**How it Works:**
1.  A user asks the e-commerce chatbot a question like "Show me some red shoes under $100."
2.  The LarAgent-powered chatbot (which has `ProductSearchTool` registered) analyzes the query.
3.  The LLM recognizes that to answer this question, it needs to search for products and identifies the parameters: `query` (shoes), `color` (red), `max_price` (100).
4.  The LLM then "calls" the `search_products` tool with these parameters.
5.  LarAgent executes the `execute` method of the `ProductSearchTool` class.
6.  Inside `execute`, the Laravel Eloquent query runs, fetching relevant products from the database.
7.  The results (e.g., "Found the following products: - Red Running Shoes (Price: $75)") are returned to the LLM.
8.  The LLM uses this information to formulate a natural language response to the user, like "Certainly! I found a pair of Red Running Shoes for $75. Would you like to see more details?"

**Benefits:**
*   **Real-time Information:** Agents can access up-to-date data from your application or external systems.
*   **Enhanced Capabilities:** Extends the AI's functionality beyond simple conversation to perform concrete actions.
*   **Dynamic Interactions:** Allows the AI to respond intelligently to complex queries that require data retrieval or system interaction.
*   **Seamless User Experience:** Users get accurate and actionable information directly from the chatbot without needing to navigate the website manually.
```

## Chat History: Remembering Conversations

**Chat History** is essential for any conversational AI. It allows your LarAgent to remember past interactions within a conversation, providing context for subsequent messages. Without chat history, every message would be treated as a new, isolated query, leading to disjointed and unhelpful responses. By maintaining history, your agent can understand follow-up questions, refer to previous topics, and provide a more natural and coherent conversational experience.

#### How Chat History Works

When you interact with an agent, each message exchanged (both user input and agent responses) is typically stored as part of the chat history. When a new message arrives, the agent sends the current message along with a portion of the previous conversation to the LLM. This context allows the LLM to generate responses that are relevant to the ongoing dialogue.

LarAgent provides several built-in drivers for managing chat history, offering different levels of persistence and storage mechanisms:

*   **`in_memory`:** This is the simplest form of history. The conversation is stored only in the agent's memory for the duration of the current request. Once the request ends, the history is lost. This is suitable for very short, stateless interactions or testing.

    ```php
    protected $history = 'in_memory';
    ```

*   **`session`:** History is stored in Laravel's session, meaning it persists for the duration of the user's web session. This is a common choice for web-based chatbots where you want the conversation to continue as the user navigates different pages.

    ```php
    protected $history = 'session';
    ```

*   **`cache`:** History is stored using Laravel's caching system. This offers more flexibility as you can configure different cache drivers (e.g., Redis, database, file) and set specific expiration times for the history. It's a good option for scalable and performant history management.

    ```php
    protected $history = 'cache';
    // Or using the class directly
    protected $history = \LarAgent\History\CacheChatHistory::class;
    ```

*   **`file`:** History is stored in a file on the server. This can be useful for debugging or for applications where simple file-based persistence is sufficient.

    ```php
    protected $history = 'file';
    ```

*   **`json`:** History is stored as a JSON string. This is often used when you want to manage history manually or integrate with a custom storage solution that expects JSON data.

    ```php
    protected $history = 'json';
    ```

#### Customizing History Behavior

While the default history drivers are often sufficient, you might need to customize how history is managed. LarAgent allows you to define your own custom history classes by implementing the `LarAgent\Contracts\ChatHistory` interface. This gives you full control over how messages are stored, retrieved, and managed.

#### Clearing Chat History

For various reasons (e.g., starting a new conversation, privacy concerns), you might need to clear an agent's chat history. LarAgent provides methods to do this:

```php
use App\AiAgents\MyAgent;

// Clear history for a specific chat session
MyAgent::for('user_123_chat')->clearHistory();

// Clear history for the current user (if using session history)
MyAgent::forUser(auth()->user())->clearHistory();
```

### Use Case Block: Chat History - Personalized Learning Assistant

```markdown
**Use Case:** Developing a Personalized Learning Assistant

**Problem:** A student is using an AI assistant to learn about complex topics. If the assistant doesn't remember previous questions or the student's learning progress, it will repeatedly ask for context or provide redundant information, leading to a frustrating experience.

**Solution with LarAgent:** Implement a learning assistant that uses persistent chat history to remember the student's questions, their current understanding, and the topics they've covered.

**Agent Configuration Example:**

```php
namespace App\AiAgents;

use LarAgent\Agent;
use LarAgent\History\CacheChatHistory; // Using cache for persistent history

class LearningAssistantAgent extends Agent
{
    protected $model = 'gpt-4o-mini';
    protected $history = CacheChatHistory::class; // Store history in cache
    protected $provider = 'default';
    protected $temperature = 0.6; // Balanced for informative responses

    public function instructions()
    {
        return "You are a patient and knowledgeable learning assistant. Your goal is to help students understand complex topics by breaking them down, answering questions, and providing examples. Remember the student's previous questions and adapt your explanations based on their current understanding. Always encourage curiosity.";
    }

    protected $tools = [
        // Potentially tools to fetch information from a knowledge base or educational resources
        // \App\Tools\KnowledgeBaseSearchTool::class,
    ];
}
```

**How it Works:**
1.  A student asks, "Explain quantum physics."
2.  The `LearningAssistantAgent` provides an initial explanation.
3.  The student follows up with, "What about entanglement?"
4.  Because `CacheChatHistory` is used, the agent sends both the new question and the previous conversation about quantum physics to the LLM.
5.  The LLM, with the full context, understands that "entanglement" is a sub-topic of quantum physics and provides a relevant explanation without needing the student to re-state the main topic.
6.  As the conversation progresses, the agent builds a richer understanding of the student's learning path, allowing for more personalized and effective guidance.

**Benefits:**
*   **Contextual Understanding:** AI remembers previous interactions, leading to more coherent conversations.
*   **Personalized Learning:** Responses can be tailored to the student's specific needs and progress.
*   **Improved User Experience:** Reduces repetition and frustration, making the AI feel more intelligent and helpful.
*   **Efficient Learning:** Students can learn more effectively as the AI builds upon prior knowledge.
```

## Structured Output: Getting Predictable Data from AI

By default, Large Language Models (LLMs) generate free-form text. While this is great for conversational responses, there are many situations where you need the AI to return data in a specific, predictable format, such as JSON, XML, or a predefined object structure. This is where **Structured Output** comes into play. LarAgent allows you to define a schema (a blueprint) for the data you expect from the AI, ensuring that the AI's response can be easily parsed and used by your application.

#### Why Structured Output?

Imagine you want an AI to extract contact information (name, email, phone) from a block of text, or to generate a list of tasks with due dates. If the AI just returns this information as a plain sentence, it's difficult for your application to reliably extract and use that data. Structured output solves this by forcing the AI to adhere to a predefined format, making it much easier to integrate AI-generated data into your application's logic, databases, or other systems.

#### Defining Structured Output

LarAgent leverages the concept of defining a schema for the expected output. This schema is typically represented as a PHP class or an array that describes the structure of the data, including the names of fields, their data types (string, integer, boolean, array, etc.), and whether they are required. The LLM then attempts to generate a response that matches this schema.

```php
// Example: Defining a schema for a recipe search result
namespace App\DataTransferObjects;

use Spatie\LaravelData\Data; // LarAgent often integrates with Spatie's Laravel Data package

class RecipeData extends Data
{
    public function __construct(
        public string $name,
        public string $cuisine,
        public array $ingredients,
        public int $prep_time_minutes,
        public ?string $instructions = null, // Optional field
    ) {}
}
```

Once you have your schema defined, you can tell your agent to use it:

```php
use App\AiAgents\RecipeAgent;
use App\DataTransferObjects\RecipeData;

$agent = RecipeAgent::for('user_recipe_session');

// Tell the agent to expect output matching the RecipeData schema
$response = $agent->respond(
    'Find me a quick Italian pasta recipe with tomatoes and basil.',
    RecipeData::class // Pass the schema class
);

// $response will now be an instance of RecipeData (or an array of RecipeData if multiple are expected)
// You can access its properties directly:
echo $response->name; // e.g., "Quick Tomato Basil Pasta"
echo implode(', ', $response->ingredients); // e.g., "pasta, tomatoes, basil, garlic"
```

**Explanation for Beginners:**

*   `RecipeData extends Data`: This is a special class that acts like a template for the data you want the AI to give you. It tells the AI, "I need a recipe, and it should have a `name` (which is text), a `cuisine` (text), `ingredients` (a list of text items), `prep_time_minutes` (a whole number), and optionally `instructions` (more text)."
*   `$agent->respond(..., RecipeData::class)`: By passing `RecipeData::class` as the second argument to the `respond` method, you are instructing the agent (and thus the underlying LLM) to format its answer according to the `RecipeData` blueprint. Instead of getting a free-form sentence, you get a structured object that you can easily work with in your PHP code.

#### Benefits of Structured Output

*   **Reliable Data Extraction:** Ensures that data returned by the AI is consistently formatted and easy to parse.
*   **Seamless Integration:** AI-generated data can be directly used in your application's database, APIs, or other components without complex parsing logic.
*   **Reduced Errors:** Minimizes the chances of misinterpreting AI responses due to inconsistent formatting.
*   **Automated Workflows:** Enables the creation of automated processes where AI extracts information and triggers subsequent actions.

### Use Case Block: Structured Output - Event Information Extractor

```markdown
**Use Case:** Extracting Event Details from Unstructured Text

**Problem:** A user pastes a block of text (e.g., from an email or a website) describing an event, and you need to extract specific details like the event name, date, time, location, and organizer in a structured format to add to a calendar or database.

**Solution with LarAgent:** Use structured output to define the exact format for event details, and have the AI extract them from the unstructured text.

**Schema Definition Example:**

```php
// app/DataTransferObjects/EventDetails.php
namespace App\DataTransferObjects;

use Spatie\LaravelData\Data;
use Carbon\Carbon; // For date/time handling

class EventDetails extends Data
{
    public function __construct(
        public string $eventName,
        public Carbon $date,
        public string $time,
        public string $location,
        public ?string $organizer = null, // Optional
        public ?string $description = null, // Optional
    ) {}
}
```

**Agent Usage Example:**

```php
use App\AiAgents\EventExtractorAgent;
use App\DataTransferObjects\EventDetails;

$eventText = "\nJoin us for the 'Annual Tech Conference' on October 26, 2025, at 9:00 AM at the Grand Convention Center, Hall B. Organized by Tech Innovators Inc. This year's conference will feature leading experts in AI and blockchain.\n";

$agent = EventExtractorAgent::for('event_extraction_session');

/** @var EventDetails $extractedEvent */
$extractedEvent = $agent->respond($eventText, EventDetails::class);

// Now you can easily access the structured data:
echo "Event Name: " . $extractedEvent->eventName; // Annual Tech Conference
echo "Date: " . $extractedEvent->date->format('Y-m-d'); // 2025-10-26
echo "Location: " . $extractedEvent->location; // Grand Convention Center, Hall B
```

**How it Works:**
1.  The user provides a block of text containing event information.
2.  The `EventExtractorAgent` receives the text and is instructed to return data conforming to the `EventDetails` schema.
3.  The LLM processes the text, identifies the relevant pieces of information (event name, date, time, etc.), and maps them to the fields defined in `EventDetails`.
4.  LarAgent then returns a `EventDetails` object, which is a structured, type-safe representation of the extracted event data.
5.  Your application can now easily store this data in a database, display it in a calendar, or use it for further processing.

**Benefits:**
*   **Automation:** Automates the tedious task of manually extracting information from unstructured text.
*   **Accuracy:** Reduces human error in data entry.
*   **Efficiency:** Speeds up processes that rely on structured data.
*   **Data Consistency:** Ensures that extracted data always adheres to a predefined format.
```

## Streaming: Real-time AI Responses

Traditionally, when you send a query to an LLM, you wait for the entire response to be generated before it's sent back to your application. For long responses, this can lead to noticeable delays and a less interactive user experience. **Streaming** solves this by sending the AI's response back to your application piece by piece, as it's being generated. This allows you to display the response to the user in real-time, similar to how ChatGPT or other modern AI interfaces work, making the interaction feel much faster and more dynamic.

#### How Streaming Works

When streaming is enabled, instead of waiting for the complete response, LarAgent receives chunks of text from the LLM as they become available. These chunks are then passed to a callback function in your application. You can then take these chunks and display them incrementally to the user, creating a live-typing effect.

```php
use App\AiAgents\ChatAgent;

$agent = ChatAgent::for('user_chat_session');

$agent->stream(function ($chunk) {
    // This function will be called repeatedly with small pieces of the response
    echo $chunk; // Output each chunk as it arrives
    flush(); // Ensure the output is sent to the browser immediately
});

$agent->respond('Tell me a long story about a knight and a dragon.');

// The story will appear character by character or word by word in the browser.
```

**Explanation for Beginners:**

*   `$agent->stream(function ($chunk) { ... });`: This line tells the agent, "Don't wait for the whole answer. As soon as you get any part of the answer, send it to this function." The `function ($chunk)` is a piece of code that will run every time a new `chunk` (a small part of the AI's response) arrives.
*   `echo $chunk;`: This simply prints the received chunk to your output (e.g., the web browser or console).
*   `flush();`: This is important for web applications. It tells the server to send the current output buffer to the user's browser immediately, rather than waiting for the entire script to finish. This creates the real-time typing effect.

#### Benefits of Streaming

*   **Improved User Experience:** Makes AI interactions feel faster and more responsive, especially for long responses.
*   **Perceived Performance:** Users see content appearing immediately, reducing perceived latency.
*   **Engagement:** Keeps users engaged as they watch the AI's response unfold in real-time.
*   **Early Feedback:** Allows users to get initial parts of the response quickly, potentially guiding their next question or confirming the AI is on the right track.

### Use Case Block: Streaming - Live Content Generation

```markdown
**Use Case:** Building a Live Blog Post Generator

**Problem:** A content creator wants to generate a long blog post using AI, but waiting for the entire post to be generated before seeing any content is inefficient and breaks their workflow.

**Solution with LarAgent:** Implement a blog post generator that streams the AI's output, allowing the creator to see the content being written in real-time and make adjustments or provide further prompts as it's generated.

**Agent Usage Example:**

```php
namespace App\Http\Controllers;

use App\AiAgents\ContentWriterAgent;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    public function generatePost(Request $request)
    {
        // Set headers for streaming response
        return response()->stream(function () use ($request) {
            $agent = ContentWriterAgent::for('blog_post_session');

            // Define the streaming callback
            $agent->stream(function ($chunk) {
                echo $chunk; // Output the chunk directly to the browser
                flush(); // Send the output immediately
            });

            // Instruct the agent to write a blog post
            $agent->respond(
                'Write a detailed blog post about the benefits of remote work, covering productivity, work-life balance, and challenges.'
            );
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
        ]);
    }
}
```

**How it Works:**
1.  The user initiates the blog post generation from a web interface.
2.  The `generatePost` controller method sets up a streaming HTTP response.
3.  The `ContentWriterAgent` is instructed to write the blog post and its `stream` method is configured.
4.  As the LLM generates parts of the blog post, LarAgent sends these chunks to the streaming callback.
5.  The callback `echo`es each chunk, and `flush()` ensures it's immediately sent to the user's browser.
6.  The user sees the blog post appearing word by word or sentence by sentence in their browser, providing an interactive and dynamic content creation experience.

**Benefits:**
*   **Interactive Content Creation:** Users can monitor and influence the generation process in real-time.
*   **Faster Feedback Loop:** Allows for immediate review and iteration on generated content.
*   **Enhanced User Experience:** Makes the AI feel more responsive and integrated into the workflow.
*   **Resource Efficiency:** Can potentially reduce server load by not holding the entire response in memory before sending.
```

## LLM Drivers: Connecting to Different AI Models

**LLM Drivers** are the components within LarAgent that handle the actual communication with various Large Language Model (LLM) providers, such as OpenAI, Google Gemini, or Groq. Think of a driver as a translator that allows your LarAgent to speak the specific language and protocols required by each AI service. This abstraction means you can switch between different LLM providers without significantly changing your agent's core logic, providing flexibility and future-proofing.

#### How LLM Drivers Work

Each LLM provider has its own API (Application Programming Interface) for sending requests and receiving responses. An LLM Driver encapsulates the logic for interacting with a specific provider's API. When your agent needs to send a prompt to an LLM, it uses the configured driver, which then translates the request into the format expected by the LLM provider, sends it, and then translates the response back into a format LarAgent can understand.

LarAgent comes with built-in drivers for popular LLM providers:

*   **OpenAI Driver:** For interacting with OpenAI's models (GPT-3.5, GPT-4, GPT-4o, etc.).
*   **Gemini Driver:** For interacting with Google's Gemini models.
*   **Groq Driver:** For interacting with Groq's fast inference models.
*   **DeepSeek Driver:** For interacting with DeepSeek's models using OpenAI-compatible API.

#### Configuring LLM Drivers and Providers

LLM drivers are typically configured in your `config/laragent.php` file. This file allows you to define different "providers," each linked to a specific driver and API key. This setup enables you to easily switch between providers or even use multiple providers within the same application.

Here's a simplified example of what your `config/laragent.php` might look like:

```php
// config/laragent.php
return [
    'default_driver' => \LarAgent\Drivers\OpenAi\OpenAiCompatible::class,
    'default_chat_history' => \LarAgent\History\InMemoryChatHistory::class,

    'providers' => [
        'default' => [
            'label' => 'openai',
            'api_key' => env('OPENAI_API_KEY'),
            'driver' => \LarAgent\Drivers\OpenAi\OpenAiDriver::class,
            'default_context_window' => 50000,
            'default_max_completion_tokens' => 10000,
            'default_temperature' => 1,
        ],

        'gemini' => [
            'label' => 'gemini',
            'api_key' => env('GEMINI_API_KEY'),
            'driver' => \LarAgent\Drivers\OpenAi\GeminiDriver::class, // Note: GeminiDriver might be under OpenAi namespace for compatibility
            'default_context_window' => 1000000,
            'default_max_completion_tokens' => 10000,
            'default_temperature' => 1,
        ],

        'groq' => [
            'label' => 'groq',
            'api_key' => env('GROQ_API_KEY'),
            'api_url' => 'https://api.groq.com/openai/v1',
            'model' => 'llama-3.1-8b-instant',
            'driver' => \LarAgent\Drivers\Groq\GroqDriver::class,
            'default_context_window' => 131072,
            'default_max_completion_tokens' => 131072,
            'default_temperature' => 1,
        ],

        'deepseek' => [
            'label' => 'deepseek',
            'api_key' => env('DEEPSEEK_API_KEY'),
            'api_url' => 'https://api.deepseek.com/v1',
            'model' => 'deepseek-chat',
            'driver' => \LarAgent\Drivers\OpenAi\OpenAiCompatible::class,
            'default_context_window' => 32768,
            'default_max_completion_tokens' => 4096,
            'default_temperature' => 0.7,
        ],
    ],

    'fallback_provider' => 'default',
];
```

**Explanation for Beginners:**

*   `'providers' => [...]`: This is the main section where you define different LLM configurations. Each key (e.g., `'default'`, `'gemini'`, `'groq'`) is a unique name for a provider setup.
*   `'api_key' => env('OPENAI_API_KEY')`: This is where you link your API key, typically stored in your `.env` file for security.
*   `'driver' => \LarAgent\Drivers\OpenAi\OpenAiDriver::class`: This specifies which LarAgent driver to use for this provider. This is the "translator" for that specific LLM service.
*   `'default_context_window'`, `'default_max_completion_tokens'`, `'default_temperature'`: These are default settings for the models used by this provider. You can override these at the agent level.

In your agent class, you then specify which provider to use:

```php
namespace App\AiAgents;

use LarAgent\Agent;

class MyAgent extends Agent
{
    protected $provider = 'gemini'; // This agent will use the 'gemini' provider configuration
    protected $model = 'gemini-pro'; // And specifically the 'gemini-pro' model
    // ...
}
```

#### Benefits of LLM Drivers

*   **Flexibility:** Easily switch between different LLM providers without rewriting your agent logic.
*   **Abstraction:** Your agent code remains clean and doesn't need to know the specifics of each LLM API.
*   **Future-Proofing:** As new LLMs emerge, new drivers can be added to LarAgent, allowing your applications to leverage them with minimal changes.
*   **Cost Optimization:** You can configure agents to use different providers or models based on cost, performance, or specific task requirements.

#### DeepSeek Integration: Cost-Effective AI Alternative

DeepSeek is a powerful and cost-effective alternative to traditional LLM providers, offering competitive performance at a fraction of the cost. LarAgent supports DeepSeek through its OpenAI-compatible API, making it easy to integrate into your existing Laravel applications.

**Key Benefits of DeepSeek:**
*   **Cost-Effective:** Significantly lower pricing compared to OpenAI's GPT models
*   **High Performance:** Strong reasoning and coding capabilities
*   **OpenAI Compatible:** Uses the same API structure as OpenAI, making integration seamless
*   **Fast Response Times:** Quick inference speeds for real-time applications

**DeepSeek Configuration Example:**

```php
// config/laragent.php
'providers' => [
    'deepseek' => [
        'label' => 'deepseek',
        'api_key' => env('DEEPSEEK_API_KEY'),
        'api_url' => 'https://api.deepseek.com/v1',
        'model' => 'deepseek-chat',
        'driver' => \LarAgent\Drivers\OpenAi\OpenAiCompatible::class,
        'default_context_window' => 32768,
        'default_max_completion_tokens' => 4096,
        'default_temperature' => 0.7,
    ],
],
```

**Environment Variables (.env):**
```env
DEEPSEEK_API_KEY=your_deepseek_api_key_here
```

**Agent Usage with DeepSeek:**
```php
namespace App\AiAgents;

use LarAgent\Agent;

class DeepSeekAgent extends Agent
{
    protected $provider = 'deepseek';
    protected $model = 'deepseek-chat';
    protected $temperature = 0.7;

    public function instructions()
    {
        return "You are a helpful and efficient AI assistant powered by DeepSeek.";
    }
}

// Usage
$response = DeepSeekAgent::for('user_session')->respond('Explain quantum computing in simple terms.');
```

**Testing DeepSeek Integration:**

You can test your DeepSeek integration using Laravel's Artisan commands. Create a test command to verify everything is working:

```php
// app/Console/Commands/TestDeepSeekAgent.php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\AiAgents\DeepSeekExampleAgent;

class TestDeepSeekAgent extends Command
{
    protected $signature = 'test:deepseek {message?}';
    protected $description = 'Test the DeepSeek agent with a message';

    public function handle()
    {
        $message = $this->argument('message') ?? 'Hello, can you help me with a simple coding question?';
        
        $this->info("Testing DeepSeek Agent...");
        $this->info("Message: {$message}");
        
        try {
            $agent = DeepSeekExampleAgent::for('test_session');
            $response = $agent->respond($message);
            
            $this->info("Response:");
            $this->line($response);
            
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            $this->error("Make sure you have set DEEPSEEK_API_KEY in your .env file");
        }
    }
}
```

**Run the test:**
```bash
# Test with default message
php artisan test:deepseek

# Test with custom message
php artisan test:deepseek "Write a simple PHP function to calculate factorial"
```

### Use Case Block: LLM Drivers - Multi-Model AI Application

```markdown
**Use Case:** Building a Multi-Model AI Application for Different Tasks

**Problem:** You have an application that needs to perform various AI tasks: one requires highly creative text generation (e.g., marketing copy), another needs fast, concise answers (e.g., a chatbot), and a third requires powerful reasoning (e.g., complex data analysis). Using a single LLM for all tasks might be suboptimal in terms of cost, speed, or quality.

**Solution with LarAgent:** Configure different LLM providers and models within LarAgent and assign specific agents to use the most suitable model for their task.

**Configuration Example (config/laragent.php):**

```php
return [
    // ... other configurations

    'providers' => [
        'creative_openai' => [
            'label' => 'openai',
            'api_key' => env('OPENAI_API_KEY'),
            'driver' => \LarAgent\Drivers\OpenAi\OpenAiDriver::class,
            'default_model' => 'gpt-4o', // More powerful, creative model
            'default_temperature' => 0.9,
        ],

        'fast_groq' => [
            'label' => 'groq',
            'api_key' => env('GROQ_API_KEY'),
            'api_url' => 'https://api.groq.com/openai/v1',
            'model' => 'llama-3.1-8b-instant', // Very fast, cost-effective
            'driver' => \LarAgent\Drivers\Groq\GroqDriver::class,
            'default_temperature' => 0.5,
        ],

        'analytical_gemini' => [
            'label' => 'gemini',
            'api_key' => env('GEMINI_API_KEY'),
            'driver' => \LarAgent\Drivers\OpenAi\GeminiDriver::class,
            'default_model' => 'gemini-1.5-pro', // Good for complex reasoning
            'default_temperature' => 0.3,
        ],

        'cost_effective_deepseek' => [
            'label' => 'deepseek',
            'api_key' => env('DEEPSEEK_API_KEY'),
            'api_url' => 'https://api.deepseek.com/v1',
            'model' => 'deepseek-chat', // Cost-effective alternative
            'driver' => \LarAgent\Drivers\OpenAi\OpenAiCompatible::class,
            'default_temperature' => 0.7,
        ],
    ],

    'fallback_provider' => 'default',
];
```

**Agent Usage Example:**

```php
namespace App\AiAgents;

use LarAgent\Agent;

// Agent for generating marketing copy
class MarketingCopyAgent extends Agent
{
    protected $provider = 'creative_openai';
    protected $model = 'gpt-4o';
    public function instructions() { return "You are a highly creative marketing copywriter."; }
}

// Agent for a quick Q&A chatbot
class QuickChatbotAgent extends Agent
{
    protected $provider = 'fast_groq';
    protected $model = 'llama-3.1-8b-instant';
    public function instructions() { return "You are a concise and helpful assistant."; }
}

// Agent for complex data analysis
class DataAnalysisAgent extends Agent
{
    protected $provider = 'analytical_gemini';
    protected $model = 'gemini-1.5-pro';
    public function instructions() { return "You are an expert data analyst."; }
    protected $tools = [\App\Tools\DatabaseQueryTool::class];
}

// Agent for cost-effective general tasks
class GeneralPurposeAgent extends Agent
{
    protected $provider = 'cost_effective_deepseek';
    protected $model = 'deepseek-chat';
    public function instructions() { return "You are a helpful and efficient assistant."; }
}

// In your application logic:
$marketingText = MarketingCopyAgent::for('marketing_session')->respond('Write a catchy slogan for a new coffee shop.');
$chatbotResponse = QuickChatbotAgent::for('chat_session')->respond('What is the capital of France?');
$analysisResult = DataAnalysisAgent::for('analysis_session')->respond('Analyze sales data for Q3 2024 and identify key trends.');
$generalResponse = GeneralPurposeAgent::for('general_session')->respond('Help me write a professional email to a client.');
```

**How it Works:**
1.  By defining multiple providers in `config/laragent.php`, you create distinct configurations for different LLMs, each optimized for specific needs (creativity, speed, reasoning).
2.  Each agent class is then configured to use the most appropriate provider and model for its intended task.
3.  When your application calls an agent, LarAgent automatically uses the correct LLM driver and settings, ensuring that the right AI model is used for the job.

**Benefits:**
*   **Optimized Performance:** Use the best LLM for each specific task, balancing speed, cost, and quality.
*   **Cost Efficiency:** Leverage cheaper, faster models for simple tasks and more expensive, powerful models only when necessary.
*   **Enhanced Capabilities:** Access the unique strengths of different LLMs within a single application.
*   **Scalability:** Easily add or remove LLM providers as your needs evolve.
```

## Expose Agents via API: Building AI-Powered Services

One of the most powerful features of LarAgent is the ability to **expose your AI agents as API endpoints**. This means you can turn your intelligent agents into backend services that can be consumed by any other application – a mobile app, a frontend JavaScript application, another microservice, or even a third-party system. This transforms your Laravel application into a hub for AI-powered services, making your agents accessible and reusable across your entire ecosystem.

#### Why Expose Agents via API?

*   **Decoupling:** Separates your AI logic from your frontend or other backend services, promoting a cleaner architecture.
*   **Reusability:** The same agent can be used by multiple client applications.
*   **Scalability:** API endpoints can be easily scaled independently of your main Laravel application.
*   **Integration:** Allows non-Laravel applications to leverage your AI agents.
*   **Security:** You can implement standard API security measures (authentication, authorization) to control access to your agents.

#### How to Expose Agents via API

LarAgent simplifies the process of exposing agents. While the documentation doesn't provide a direct `expose_agent_api` command, the general approach involves creating a Laravel route and controller that interacts with your agent and returns its response as a JSON API.

**Step 1: Define Your Agent (as discussed previously)**

Let's assume you have a `ProductRecommendationAgent`:

```php
// app/AiAgents/ProductRecommendationAgent.php
namespace App\AiAgents;

use LarAgent\Agent;
use App\Tools\ProductSearchTool;

class ProductRecommendationAgent extends Agent
{
    protected $model = 'gpt-4o-mini';
    protected $tools = [
        ProductSearchTool::class, // Agent uses a tool to search products
    ];

    public function instructions()
    {
        return "You are a helpful product recommendation assistant. Recommend products based on user preferences and available stock.";
    }
}
```

**Step 2: Create an API Route**

Define a route in your `routes/api.php` file that will serve as the endpoint for your agent.

```php
// routes/api.php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AgentApiController;

Route::post('/recommend-product', [AgentApiController::class, 'recommend']);
```

**Step 3: Create an API Controller**

Create a controller that handles the incoming API request, interacts with your agent, and returns a JSON response.

```php
// app/Http/Controllers/AgentApiController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\AiAgents\ProductRecommendationAgent;
use Illuminate\Http\JsonResponse;

class AgentApiController extends Controller
{
    public function recommend(Request $request): JsonResponse
    {
        $userQuery = $request->input('query');

        if (empty($userQuery)) {
            return response()->json(['error' => 'Query parameter is required.'], 400);
        }

        try {
            $agent = ProductRecommendationAgent::for('api_session_' . $request->ip()); // Use IP for session ID
            $response = $agent->respond($userQuery);

            return response()->json(['recommendation' => $response]);
        } catch (\Exception $e) {
            // Log the error for debugging
            
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }
}
```

**Explanation for Beginners:**

*   `Route::post('/recommend-product', ...)`: This sets up an API endpoint that listens for `POST` requests at `/api/recommend-product`.
*   `AgentApiController::class, 'recommend'`: This tells Laravel to execute the `recommend` method in the `AgentApiController` when a request hits this route.
*   `$request->input('query')`: This retrieves the user's query from the incoming API request (e.g., from a JSON payload).
*   `ProductRecommendationAgent::for(...)`: This initializes your agent. We're using the user's IP address to create a unique session ID for chat history, but you could use a user ID or a custom session token.
*   `$agent->respond($userQuery)`: This is where your agent processes the query and generates a response.
*   `return response()->json(...)`: This sends the agent's response back to the client as a JSON object, which is a standard format for APIs.

#### Benefits of Exposing Agents via API

*   **Universal Access:** Any application capable of making HTTP requests can now use your AI agents.
*   **Centralized AI Logic:** Manage all your AI agents in one Laravel application, providing a single source of truth for your AI capabilities.
*   **Scalable AI Services:** Deploy your Laravel application as a dedicated AI service, scaling it independently to handle AI workloads.
*   **Microservices Architecture:** Fits well into a microservices approach, where AI functionality is provided by a dedicated service.

### Use Case Block: Expose Agents via API - Mobile App AI Assistant

```markdown
**Use Case:** Powering a Mobile Application with an AI Assistant

**Problem:** You have a mobile application (e.g., a fitness tracker, a travel planner) and want to add an AI assistant feature. The mobile app needs to communicate with a backend service to get AI responses.

**Solution with LarAgent:** Expose a LarAgent-powered AI assistant as an API endpoint in your Laravel backend. The mobile app will then send user queries to this API and display the AI's responses.

**Backend (Laravel) Setup:**

```php
// app/AiAgents/FitnessCoachAgent.php
namespace App\AiAgents;

use LarAgent\Agent;
use App\Tools\WorkoutPlanTool; // Tool to generate workout plans
use App\Tools\NutritionAdviceTool; // Tool to provide nutrition advice

class FitnessCoachAgent extends Agent
{
    protected $model = 'gpt-4o-mini';
    protected $tools = [
        WorkoutPlanTool::class,
        NutritionAdviceTool::class,
    ];

    public function instructions()
    {
        return "You are a friendly and knowledgeable fitness coach. Provide workout plans, nutrition advice, and answer fitness-related questions. Always encourage healthy habits.";
    }
}

// app/Http/Controllers/MobileApiAgentController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\AiAgents\FitnessCoachAgent;
use Illuminate\Http\JsonResponse;

class MobileApiAgentController extends Controller
{
    public function chat(Request $request): JsonResponse
    {
        $userQuery = $request->input('message');
        $userId = $request->input('user_id'); // Assuming user_id is sent from mobile app

        if (empty($userQuery) || empty($userId)) {
            return response()->json(['error' => 'Message and user_id are required.'], 400);
        }

        try {
            // Use user_id for chat history session to maintain context per user
            $agent = FitnessCoachAgent::for('mobile_user_' . $userId);
            $response = $agent->respond($userQuery);

            return response()->json(['reply' => $response]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'AI service error: ' . $e->getMessage()], 500);
        }
    }
}

// routes/api.php
Route::post('/mobile/fitness-coach', [MobileApiAgentController::class, 'chat']);
```

**Mobile App (Conceptual) Interaction:**

```javascript
// Example using JavaScript (e.g., in React Native, Flutter, or any web-based mobile framework)
async function sendMessageToAI(message, userId) {
    const response = await fetch('https://your-laravel-app.com/api/mobile/fitness-coach', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            // Add authentication headers if needed (e.g., Bearer Token)
        },
        body: JSON.stringify({
            message: message,
            user_id: userId
        })
    });

    const data = await response.json();
    if (response.ok) {
        console.log('AI Reply:', data.reply);
        // Display data.reply in the mobile app's chat interface
    } else {
        console.error('Error:', data.error);
        // Show error to user
    }
}

// Example usage:
sendMessageToAI('Give me a 30-minute cardio workout plan.', 'user_abc_123');
```

**How it Works:**
1.  The mobile application sends a user's query (e.g., "Give me a workout plan") along with a `user_id` to the `/mobile/fitness-coach` API endpoint.
2.  The `MobileApiAgentController` receives the request, initializes the `FitnessCoachAgent` using the `user_id` to maintain a unique chat history for that mobile user.
3.  The agent processes the query, potentially using its `WorkoutPlanTool` to generate a tailored plan.
4.  The agent's response is returned as a JSON object to the mobile app.
5.  The mobile app receives the JSON response and displays the AI's reply to the user.

**Benefits:**
*   **Cross-Platform AI:** The same AI logic can power multiple mobile platforms (iOS, Android) and even web applications.
*   **Centralized Logic:** All AI-related business logic resides in your Laravel backend, making it easier to update and maintain.
*   **Scalable Backend:** The Laravel API can be scaled independently to handle increasing mobile user demand.
*   **Rich User Experience:** Provides mobile users with an intelligent, interactive assistant.
```

## Artisan Commands: Command-Line AI Management

Laravel's **Artisan commands** are a powerful feature that allows you to interact with your application from the command line. LarAgent extends this by providing its own Artisan commands, primarily for scaffolding (generating) new agents. This makes the development workflow faster and more consistent, as you can quickly create the basic structure for your AI agents without manually creating files and writing boilerplate code.

#### How Artisan Commands Work with LarAgent

Artisan commands are executed in your terminal and perform specific tasks defined by your application. For LarAgent, the most common command is `make:agent`, which automates the initial setup of a new agent class.

```bash
php artisan make:agent MyNewAgent
```

When you run this command, LarAgent will:
1.  Create a new PHP file (e.g., `MyNewAgent.php`) in your `app/AiAgents` directory (or a configured namespace).
2.  Populate this file with the basic class structure for an agent, extending `LarAgent\Agent`.
3.  Include default properties and methods (like `instructions()` and `prompt()`) that you can then customize.

This saves you time and ensures that all your agents follow a consistent structure, which is crucial for maintainability and collaboration in larger projects.

#### Benefits of Artisan Commands

*   **Rapid Development:** Quickly scaffold new agents, reducing manual setup time.
*   **Consistency:** Ensures all agents adhere to a predefined structure and best practices.
*   **Ease of Use:** Familiar to Laravel developers, integrating seamlessly into the existing workflow.
*   **Automation:** Can be integrated into development scripts or CI/CD pipelines for automated agent creation.

### Use Case Block: Artisan Commands - Project Initialization for AI Features

```markdown
**Use Case:** Streamlining the Setup of New AI Features in a Project

**Problem:** A development team frequently adds new AI-powered features (e.g., a new type of content generator, a specialized data analysis tool). Manually creating agent files, setting up namespaces, and adding boilerplate code for each new feature is repetitive and prone to errors.

**Solution with LarAgent:** Use the `php artisan make:agent` command as a standard part of the development process to quickly initialize new AI agents.

**Development Workflow Example:**

1.  **Developer needs a new AI feature:** "I need an agent that can summarize long articles."
2.  **Run Artisan command:**
    ```bash
    php artisan make:agent ArticleSummarizerAgent
    ```
3.  **Customize the generated agent:** The developer opens `app/AiAgents/ArticleSummarizerAgent.php` and fills in the specific `instructions`, configures the `model`, and potentially adds `tools` for fetching article content.

    ```php
    // app/AiAgents/ArticleSummarizerAgent.php
    namespace App\AiAgents;

    use LarAgent\Agent;
    // use App\Tools\ArticleFetcherTool; // Assuming a tool to get article text

    class ArticleSummarizerAgent extends Agent
    {
        protected $model = 'gpt-4o-mini';
        // protected $tools = [ArticleFetcherTool::class];

        public function instructions()
        {
            return "You are an expert article summarizer. Condense long articles into concise, informative summaries, highlighting key points.";
        }

        // ... other configurations and methods
    }
    ```

4.  **Integrate into application:** The developer then integrates this new agent into a controller or service to provide the summarization functionality.

**Benefits:**
*   **Accelerated Feature Development:** Reduces the time spent on boilerplate, allowing developers to focus on core AI logic.
*   **Standardization:** Ensures all agents are created with a consistent structure, improving code readability and maintainability.
*   **Reduced Errors:** Minimizes typos and structural mistakes that can occur with manual file creation.
*   **Onboarding:** New team members can quickly get up to speed on creating AI agents.
```

## Usage Without Laravel: Beyond the Framework

While LarAgent is built specifically for Laravel, the documentation mentions "Usage Without Laravel." This implies that certain core components or concepts of LarAgent might be adaptable or inspiring for use in non-Laravel PHP applications, or that the underlying principles can be applied more broadly. While the framework itself is deeply integrated with Laravel's ecosystem, understanding this aspect highlights its architectural design and potential for broader application of its ideas.

**Key Takeaway for Beginners:** If you're working within a Laravel project, LarAgent is your go-to. If you're in a different PHP environment, you might not use LarAgent directly, but you can learn from its approach to building AI agents and tools.

## Agent Events: Reacting to AI Actions

**Agent Events** provide a powerful mechanism to hook into the lifecycle of your AI agents and react to specific actions or states. Just like Laravel's event system, LarAgent dispatches events when certain things happen (e.g., an agent starts processing a request, a tool is called, a response is generated). By listening for these events, you can implement custom logic, logging, monitoring, or integrate with other parts of your application without modifying the core agent code.

#### Why Use Agent Events?

*   **Monitoring and Logging:** Track agent activity, performance, and potential errors.
*   **Debugging:** Gain insights into the agent's decision-making process.
*   **Integration:** Trigger other application processes based on agent actions (e.g., update a database record when a specific tool is used).
*   **Analytics:** Collect data on how users interact with your AI agents.
*   **Customization:** Add custom behavior without extending or modifying core LarAgent classes.

#### How Agent Events Work

LarAgent dispatches events at various points during an agent's operation. You can create Laravel event listeners that respond to these events. The specific events and their payloads (data they carry) would be detailed in the LarAgent source code or more advanced documentation.

**Conceptual Example (assuming LarAgent dispatches a `ToolCalled` event):**

1.  **Define an Event Listener:**

    ```php
    // app/Listeners/LogToolUsage.php
    namespace App\Listeners;

    use App\Events\LarAgent\ToolCalled; // Assuming this event exists
    use Illuminate\Contracts\Queue\ShouldQueue;
    use Illuminate\Queue\InteractsWithQueue;
    use Illuminate\Support\Facades\Log;

    class LogToolUsage implements ShouldQueue
    {
        use InteractsWithQueue;

        public function handle(ToolCalled $event)
        {
            Log::info('LarAgent Tool Used', [
                'agent_class' => get_class($event->agent),
                'tool_name' => $event->toolName,
                'parameters' => $event->parameters,
                'user_id' => $event->userId ?? null,
            ]);
        }
    }
    ```

2.  **Register the Listener:**

    In your `app/Providers/EventServiceProvider.php`:

    ```php
    // app/Providers/EventServiceProvider.php
    protected $listen = [
        \App\Events\LarAgent\ToolCalled::class => [
            \App\Listeners\LogToolUsage::class,
        ],
        // ... other events
    ];
    ```

**Explanation for Beginners:**

*   `ToolCalled $event`: When a tool is used by your agent, LarAgent sends out a signal (an "event"). This signal carries information about which tool was used, by which agent, and with what inputs. Your `handle` method receives this information.
*   `Log::info(...)`: This line simply writes a message to your Laravel application's log file, recording that a tool was used and providing details. This is great for monitoring and debugging.

#### Benefits of Agent Events

*   **Observability:** Provides a clear way to see what your agents are doing behind the scenes.
*   **Extensibility:** Add custom logic without modifying LarAgent's core, making updates easier.
*   **Loose Coupling:** Decouples monitoring, logging, and integration logic from the agent's primary function.
*   **Audit Trails:** Create detailed records of AI interactions and tool usage.

### Use Case Block: Agent Events - AI Usage Monitoring and Billing

```markdown
**Use Case:** Monitoring AI Agent Usage for Analytics or Billing

**Problem:** You have multiple AI agents in your application, and you need to track how often each agent is used, which tools are invoked, and potentially calculate costs associated with LLM API calls or tool executions for internal analytics or client billing.

**Solution with LarAgent:** Utilize Agent Events to capture detailed usage data whenever an agent processes a request or calls a tool.

**Event Listener Example:**

```php
// app/Listeners/TrackAgentUsage.php
namespace App\Listeners;

use App\Events\LarAgent\AgentResponded; // Assuming an event dispatched after agent responds
use App\Events\LarAgent\ToolCalled; // Assuming an event dispatched when a tool is called
use App\Models\AgentUsageLog; // Your Eloquent model for logging usage
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class TrackAgentUsage implements ShouldQueue
{
    use InteractsWithQueue;

    public function handleAgentResponded(AgentResponded $event)
    {
        AgentUsageLog::create([
            'agent_class' => get_class($event->agent),
            'user_id' => $event->userId ?? null,
            'prompt_tokens' => $event->promptTokens,
            'completion_tokens' => $event->completionTokens,
            'type' => 'response',
            'details' => json_encode(['message' => $event->message]),
        ]);
    }

    public function handleToolCalled(ToolCalled $event)
    {
        AgentUsageLog::create([
            'agent_class' => get_class($event->agent),
            'user_id' => $event->userId ?? null,
            'tool_name' => $event->toolName,
            'type' => 'tool_call',
            'details' => json_encode($event->parameters),
        ]);
    }
}
```

**Registration in `EventServiceProvider`:**

```php
// app/Providers/EventServiceProvider.php
protected $listen = [
    \App\Events\LarAgent\AgentResponded::class => [
        \App\Listeners\TrackAgentUsage::class . '@handleAgentResponded',
    ],
    \App\Events\LarAgent\ToolCalled::class => [
        \App\Listeners\TrackAgentUsage::class . '@handleToolCalled',
    ],
];
```

**How it Works:**
1.  Whenever an agent finishes responding (`AgentResponded` event) or calls a tool (`ToolCalled` event), LarAgent dispatches the corresponding event.
2.  The `TrackAgentUsage` listener catches these events.
3.  Inside the listener, relevant data (agent class, user ID, token usage, tool name, parameters) is extracted from the event object.
4.  This data is then stored in a dedicated `AgentUsageLog` database table, providing a comprehensive record of all AI interactions.

**Benefits:**
*   **Granular Usage Data:** Collect detailed metrics on AI agent and tool utilization.
*   **Cost Attribution:** Accurately track LLM token usage for billing or cost analysis.
*   **Performance Monitoring:** Identify frequently used agents or tools and optimize them.
*   **Business Intelligence:** Gain insights into how users are interacting with your AI features.
```

## Engine Hooks: Deeper Customization

**Engine Hooks** (or just "Hooks") in LarAgent provide an even deeper level of customization and control over the AI agent's internal processing. While events allow you to *react* to something that has already happened, hooks allow you to *intervene* in the process, modify data, or alter behavior *before* or *during* a specific operation. They are essentially points in the LarAgent's internal workflow where you can inject your own custom logic.

#### Why Use Engine Hooks?

*   **Data Transformation:** Modify prompts or responses before they are sent to/from the LLM.
*   **Pre-processing/Post-processing:** Add custom logic before an agent responds or after a tool is executed.
*   **Validation:** Implement custom validation rules for inputs or outputs.
*   **Dynamic Behavior:** Change agent behavior based on complex runtime conditions.
*   **Advanced Logging/Debugging:** Capture very specific internal states for detailed analysis.

#### How Engine Hooks Work

LarAgent's documentation mentions `beforeStructuredOutput` as an example of a hook. This suggests that you can define methods within your agent or a related class that LarAgent will automatically call at specific points. These methods receive certain data as arguments and can modify that data or perform actions.

**Conceptual Example: `beforeStructuredOutput` Hook**

If you define a structured output schema, LarAgent might provide a hook that allows you to modify the raw LLM response *before* it's parsed into your structured object. This could be useful for cleaning up the raw text or handling edge cases.

```php
namespace App\AiAgents;

use LarAgent\Agent;
use App\DataTransferObjects\EventDetails; // Your structured output schema

class EventExtractorAgent extends Agent
{
    // ... other properties

    /**
     * This method is a conceptual hook that might be called by LarAgent
     * before the raw LLM response is converted into the structured output object.
     * It allows you to modify the raw response.
     *
     * @param string $rawLlMResponse The raw text response from the LLM.
     * @return string The potentially modified raw response.
     */
    public function beforeStructuredOutput(string $rawLlMResponse): string
    {
        // Example: Clean up common formatting issues before parsing
        $cleanedResponse = str_replace('```json', '', $rawLlMResponse);
        $cleanedResponse = str_replace('```', '', $cleanedResponse);
        $cleanedResponse = trim($cleanedResponse);

        // Log the raw and cleaned response for debugging
        
        return $cleanedResponse;
    }

    // ... rest of your agent methods
}
```

**Explanation for Beginners:**

*   `beforeStructuredOutput(string $rawLlMResponse)`: This is a special method name that LarAgent recognizes as a "hook." When the AI generates a response that's supposed to be structured, LarAgent will call this method *before* it tries to turn the raw text into your structured data object (like `EventDetails`).
*   `return $cleanedResponse;`: Whatever this method returns will be the text that LarAgent then tries to parse into your structured output. This gives you a chance to fix any issues in the raw text before parsing.

**Important Note:** The exact names and parameters of hooks would be defined by the LarAgent framework. This is a conceptual example to illustrate the power of hooks.

#### Benefits of Engine Hooks

*   **Granular Control:** Intervene at specific points in the agent's processing pipeline.
*   **Advanced Customization:** Implement complex logic that goes beyond simple event reactions.
*   **Data Manipulation:** Modify data (prompts, responses) on the fly.
*   **Debugging and Diagnostics:** Gain deep insights into the agent's internal workings.

### Use Case Block: Engine Hooks - Input Sanitization for Sensitive Data

```markdown
**Use Case:** Sanitizing User Input Before Sending to LLM

**Problem:** Users might accidentally (or intentionally) include sensitive personal information (PII) like credit card numbers, social security numbers, or private keys in their chat messages. Sending this raw sensitive data to an external LLM service could pose a security risk.

**Solution with LarAgent:** Implement an engine hook that sanitizes or redacts sensitive information from the user's prompt *before* it is sent to the LLM.

**Conceptual Hook Example (assuming a `beforePromptSentToLLM` hook):**

```php
namespace App\AiAgents;

use LarAgent\Agent;

class SensitiveDataAgent extends Agent
{
    // ... other properties

    /**
     * This conceptual hook is called before the prompt is sent to the LLM.
     * It allows modification of the prompt content.
     *
     * @param string $prompt The prompt string about to be sent to the LLM.
     * @return string The modified prompt string.
     */
    public function beforePromptSentToLLM(string $prompt): string
    {
        // Example: Redact credit card numbers (simple regex for illustration)
        $sanitizedPrompt = preg_replace('/\b(?:\d[ -]*?){13,16}\b/', '[REDACTED_CREDIT_CARD]', $prompt);

        // Example: Redact email addresses
        $sanitizedPrompt = preg_replace('/\S+@\S+\.\S+/', '[REDACTED_EMAIL]', $sanitizedPrompt);

        // Log the original and sanitized prompt for audit (carefully, without sensitive data)
        
        return $sanitizedPrompt;
    }

    // ... rest of your agent methods
}
```

**How it Works:**
1.  A user sends a message to the `SensitiveDataAgent` that might contain sensitive information.
2.  LarAgent's internal process triggers the `beforePromptSentToLLM` hook (if it exists and is configured).
3.  The `beforePromptSentToLLM` method receives the user's raw prompt.
4.  Inside this method, regular expressions or other sanitization logic are applied to identify and replace sensitive patterns with placeholders (e.g., `[REDACTED_CREDIT_CARD]`).
5.  The *sanitized* prompt is then returned by the hook and sent to the external LLM service.

**Benefits:**
*   **Enhanced Security:** Prevents sensitive user data from being transmitted to third-party LLM providers.
*   **Data Privacy Compliance:** Helps in adhering to data privacy regulations (e.g., GDPR, CCPA).
*   **Risk Mitigation:** Reduces the risk of data breaches or misuse of sensitive information.
*   **Customizable Sanitization:** Allows you to define specific rules for what constitutes sensitive data and how it should be handled.
```

## Conclusion

LarAgent provides a robust and developer-friendly framework for integrating AI agents into your Laravel applications. By understanding its core concepts—Agents, Tools, Chat History, Structured Output, Streaming, LLM Drivers, Artisan Commands, Agent Events, and Engine Hooks—you can build sophisticated, intelligent applications that leverage the power of Large Language Models. Whether you're creating a simple chatbot, an e-commerce assistant, or a complex data analysis tool, LarAgent offers the flexibility and features needed to bring your AI ideas to life within the familiar Laravel ecosystem.

This detailed analysis aims to serve as a comprehensive guide for beginners, breaking down complex topics into understandable explanations with practical code examples and real-world use cases. As you delve deeper into LarAgent, remember to consult the [Official LarAgent Documentation](https://docs.laragent.ai/) for the most up-to-date and in-depth information.

---

**References:**

[1] Official LarAgent Documentation. (n.d.). *Introduction*. Retrieved from [https://docs.laragent.ai/introduction](https://docs.laragent.ai/introduction)
[2] Official LarAgent Documentation. (n.d.). *Quickstart*. Retrieved from [https://docs.laragent.ai/quickstart](https://docs.laragent.ai/quickstart)
[3] Official LarAgent Documentation. (n.d.). *Agents*. Retrieved from [https://docs.laragent.ai/core-concepts/agents](https://docs.laragent.ai/core-concepts/agents)
[4] Official LarAgent Documentation. (n.d.). *Tools*. Retrieved from [https://docs.laragent.ai/core-concepts/tools](https://docs.laragent.ai/core-concepts/tools)
[5] Official LarAgent Documentation. (n.d.). *Chat History*. Retrieved from [https://docs.laragent.ai/core-concepts/chat-history](https://docs.laragent.ai/core-concepts/chat-history)
[6] Official LarAgent Documentation. (n.d.). *Structured Output*. Retrieved from [https://docs.laragent.ai/core-concepts/structured-output](https://docs.laragent.ai/core-concepts/structured-output)
[7] Official LarAgent Documentation. (n.d.). *Streaming*. Retrieved from [https://docs.laragent.ai/core-concepts/streaming](https://docs.laragent.ai/core-concepts/streaming)
[8] Official LarAgent Documentation. (n.d.). *LLM Drivers*. Retrieved from [https://docs.laragent.ai/llm-drivers](https://docs.laragent.ai/llm-drivers)
[9] Official LarAgent Documentation. (n.d.). *Expose Agents via API*. Retrieved from [https://docs.laragent.ai/expose-agents-via-api](https://docs.laragent.ai/expose-agents-via-api)
[10] Official LarAgent Documentation. (n.d.). *Artisan Commands*. Retrieved from [https://docs.laragent.ai/artisan-commands](https://docs.laragent.ai/artisan-commands)
[11] Official LarAgent Documentation. (n.d.). *Usage Without Laravel*. Retrieved from [https://docs.laragent.ai/usage-without-laravel](https://docs.laragent.ai/usage-without-laravel)
[12] Official LarAgent Documentation. (n.d.). *Agent Events*. Retrieved from [https://docs.laragent.ai/extensibility-customization/agent-events](https://docs.laragent.ai/extensibility-customization/agent-events)
[13] Official LarAgent Documentation. (n.d.). *Engine Hooks*. Retrieved from [https://docs.laragent.ai/extensibility-customization/engine-hooks](https://docs.laragent.ai/extensibility-customization/engine-hooks)


