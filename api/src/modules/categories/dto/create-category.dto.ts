import { IsNotEmpty, IsOptional, IsString, IsIn } from 'class-validator';
import { ApiProperty } from '@nestjs/swagger';

export class CreateCategoryDto {
  @ApiProperty({ example: 'income', enum: ['income', 'expense'] })
  @IsNotEmpty()
  @IsIn(['income', 'expense'])
  type: string;

  @ApiProperty({ example: 'أقساط شهرية' })
  @IsNotEmpty()
  @IsString()
  name: string;

  @ApiProperty({ required: false })
  @IsOptional()
  @IsString()
  description?: string;
}
