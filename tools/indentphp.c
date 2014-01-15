/* 
 * Indentation PHP, style AE2
 *        
 * Copyright 2007
 * - Julien Etelain < julien dot etelain at gmail dot com >
 *
 * Ce fichier fait partie du site de l'Association des Étudiants de
 * l'UTBM, http://ae.utbm.fr/
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA
 * 02111-1307, USA.
 */

#include<stdio.h>
#include<stdlib.h>
#include<string.h>


void printnewline ( FILE* output, int indent )
{
  int i=0;
  
  fputc('\n',output);
  
  for(i=0;i<indent;++i)
    fprintf(output,"  ");
}


int main ( int argc, char **argv )
{
  int php=0;
  
  int quote=0,comment=0;
  char quote_type=0;
  int comment_type=0;
  int indent=0;
  int braces=0;
  int parentheses=0;
  int newline=1;
  int afterspace=0;
  int mono_indent=0;
  int newline_last_parentheses=0;
  
  int braces_prenewline=0;
  
  char c=0, prev=0,prevprev=0;
  
  FILE *input;  
  FILE *output;  
  
  char key[16];
  int key_len=0;
  
  if ( argc != 3 )
  {
    printf("Indentation PHP, style AE2\n");
    printf("Outil du site de l'Association des Etudiants de l'UTBM\n");
    printf("(c) Copyright 2007 Julien Etelain.\n\n");
    printf("Indente du code PHP avec le style AE2. Ne remet pas en forme les commentaires\n\n");
    printf("Usage: %s fichierentree fichiersortie\n",argv[0]);
    return 1;
  }
  
  input = fopen(argv[1],"rt");
  
  if ( !input )
  {
    printf("%s: Impossible d'ouvrir %s en lecture.\n",argv[0],argv[1]);
    return 2;
  }
  
  output = fopen(argv[2],"wt");
  
  if ( !output )
  {
    fclose(input);
    printf("%s: Impossible d'ouvrir %s en ecriture.\n",argv[0],argv[2]);
    return 2;
  }
  
  while ( fread(&c, 1, 1, input) == 1 )
  {
    if ( php ) 
    {
      if ( comment )
      {
        if ( comment_type == 0 && c == '/' && prev == '*' )
        {
          comment = 0;
          fputc(c,output);
        }
        else if ( comment_type == 1 && (c=='\n' || c=='\r')  )
        {
          comment = 0;
          printnewline(output,indent);
          newline=1;
        }      
        else
          fputc(c,output);
      }
      else if ( quote )
      {
        fputc(c,output);
        if( quote_type == c && ( prev != '\\' || prevprev == '\\' ) )
        {
          quote=0;
          newline=0;
          afterspace=1;
        }
      }
      else if ( c == '"' || c == '\'' )
      {
        quote=1;
        quote_type=c;
        fputc(c,output);
      }
      else if ( c == '*' && prev == '/' )
      {
        comment=1;
        comment_type=0;
        fputc(c,output);
      }
      else if ( c == '/' && prev == '/' )
      {
        comment=1;
        comment_type=1;
        fputc(c,output);
      }
      else if ( c == '\t' )
      {
        if ( !newline && !afterspace )
        {
          fputc(' ',output);
          afterspace=1;
        }
        
      }
      else if ( c == ' ' )
      {
        if ( !newline && !afterspace )
        {
          fputc(' ',output);
          afterspace=1;
          
          key[key_len]=0;
          if ( !strcmp(key,"class") || !strcmp(key,"function") )
          {
            braces_prenewline = 1;
          }
        }
      }
      else if ( c == '{' )
      {
        if ( mono_indent )
        {
          mono_indent=0;
          fseek(output,-2,SEEK_CUR);
          fputc('{',output);
        }
        else if ( braces_prenewline )
        {
          if ( !newline )
            printnewline(output,indent);
          fputc('{',output);
          indent++;
        }
        else
        {
          fputc('{',output);
          indent++;
        }
        printnewline(output,indent);
        newline=1;
      }
      else if ( c == '}' )
      {
        indent--;
        //printnewline(output,indent);
        fseek(output,-2,SEEK_CUR);
        fputc('}',output);
        printnewline(output,indent);
        newline=1;
      }
      else if ( c == '\n' )
      {
        if ( !newline )
        {
          key[key_len]=0;
          if ( !strcmp(key,"else") )
          {
            mono_indent=1;
            indent++;
            printnewline(output,indent);
          }
          else
          {
            printnewline(output,indent);
            if ( parentheses > 0 )
              fprintf(output,"  ");
          }
          newline=1;
        }
      }
      else if ( c == ';' )
      {
        fputc(';',output);
        if ( mono_indent )
        {
          indent--;
          mono_indent=0;  
        }
        printnewline(output,indent);
        newline=1;
      }
      else if ( c == '(' )
      {
        fputc('(',output);
        parentheses++;
        newline=0;
        afterspace=1;
        
        key[key_len]=0;
        if ( !strcmp(key,"if") || !strcmp(key,"elseif") || !strcmp(key,"switch") || !strcmp(key,"while") )
        {
          newline_last_parentheses=1;
          mono_indent=1;
        }
      }    
      else if ( c == ')' )
      {
        fputc(')',output);
        parentheses--;
        if ( parentheses == 0 && newline_last_parentheses )
        {
          indent++;
          printnewline(output,indent);
          newline=1;
          newline_last_parentheses=0;
        }
        else
          newline=0;
        afterspace=1;
      }
      else if ( c == '>' && prev == '?' )
      {
        php=0;
        fputc(c,output);
      }
      else if ( c != '\r' ) // Sort tous les caractères restants à l'exception du '\r' (fin de ligne unix uniquement)
      {
        // Construit la "clé" de la ligne, qui corresponds aux 15 premiers caractères (sauf les caractères traités plus haut)
        if ( newline )
          key_len=0;
          
        if ( key_len < 15 )
        {
          key[key_len]=c;
          key_len++;
        }
        
        fputc(c,output);
        newline=0; // on n'est plus sur une ligne "vierge"
        afterspace=0; // on n'est plus après un caractère de type espace (tab, espace...)
      }
    }
    else if ( c == '?' && prev == '<' ) // "<?" ouvre du code PHP 
    {
      php=1;
      fputc(c,output);
    }
    else if ( c == '\n' ) // Fin de ligne
    {
      prev=c;
      if ( fread(&c, 1, 1, input) == 1 ) // Si elle n'est pas en fin de fichier, la conserve
      {
        fputc(prev,output);
        fputc(c,output);
      }
    }
    else if ( c != '\r' ) // Sort tous les caractères à l'exception du '\r' (fin de ligne unix uniquement)
    {
      fputc(c,output);
    }  
    
    prev=c;
    prevprev=prev;
  }
  fclose(input);
  fclose(output);
  
  return 0;
}