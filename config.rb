
css_dir = "web/css"
sass_dir = "app/scss"

output_style = :compressed

sass_options = {
    :cache_location => "tmp/cache/sass"
}

require 'sass-css-importer'
add_import_path Sass::CssImporter::Importer.new("web")

class Sass::Tree::Visitors::Perform < Sass::Tree::Visitors::Base

  # Removes all comments completely
  def visit_comment(node)
    return []
  end

end
