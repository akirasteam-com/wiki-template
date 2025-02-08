# How to create your own Wiki

Welcome to My Wiki. Use the sidebar to navigate or create a new article.

## How to use Markdown?

- **# Title 1**: Title level 1.
- **## Title 2**: Title level 2.
- **_Italic_** or **_Italic_**: Text in italics.
- **__Bold__** or **__Bold__**: Text in bold.
- **- List**: Bulleted list.
- **[link text](URL)**: Create a link.
- **![alternative text](Image URL)**: Display an image.
- **`code`**: Online code.
- **```code block```**: Code block.

## Create your own Wiki

To create your own wiki using open-source code, follow these steps:

1. Clone the repository from GitHub:
    ```bash
    git clone https://github.com/akirasteam-com/wiki-template/
    ```

2. Navigate to the project directory:
    ```bash
    cd wiki-template
    ```

3. Generate the application key:
    ```bash
    php artisan key:generate
    ```

4. Run database migrations:
    ```bash
    php artisan migrate
    ```

5. Start the development server:
    ```bash
    php artisan serve
    ```

Now you can access your wiki at `http://localhost:8000`.

For more information, visit the [GitHub repository](https://github.com/akirasteam-com/wiki-template/).

Happy wiki building!
